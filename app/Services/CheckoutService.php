<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The billing transaction.
 * All prices, totals and stock are computed/validated here on the server;
 * client-side totals are never trusted.
 */
class CheckoutService
{
    public function __construct(
        private InventoryService $inventory,
        private InvoiceNumberGenerator $numbers,
        private ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, array{product_id:int, quantity:string|int|float}>  $items
     */
    public function checkout(User $cashier, Branch $branch, array $items, array $payment): Sale
    {
        $key = (string) $payment['idempotency_key'];

        // 1-2. Authenticated, active user with access to an active branch of an active shop.
        if (! $cashier->is_active || ! $cashier->hasPermission('pos.use')) {
            throw new BusinessRuleException('You are not allowed to make sales.');
        }
        if ($branch->shop_id !== $cashier->shop_id || ! $cashier->canAccessBranch($branch) || ! $branch->is_active) {
            throw new BusinessRuleException('You are not allowed to sell from this branch.');
        }

        // Idempotency: a retried/double-submitted checkout returns the original sale.
        if ($existing = $this->findExisting($cashier, $key)) {
            return $existing;
        }

        // 3. Validate & normalize cart (merge duplicate lines).
        $lines = [];
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = Money::qtyToMilli((string) ($item['quantity'] ?? '0'));
            if ($pid <= 0 || $qty <= 0) {
                throw new BusinessRuleException('Each cart item needs a product and a quantity greater than zero.');
            }
            $lines[$pid] = ($lines[$pid] ?? 0) + $qty;
        }
        if (! $lines) {
            throw new BusinessRuleException('The cart is empty.');
        }
        ksort($lines); // consistent lock order prevents deadlocks

        try {
            // 4. Begin transaction (retried automatically on deadlock).
            return DB::transaction(function () use ($cashier, $branch, $lines, $payment, $key) {
                $settings = $branch->shop->config();
                $products = Product::where('shop_id', $branch->shop_id)->whereIn('id', array_keys($lines))
                    ->where('is_active', true)->get()->keyBy('id');

                // 5. Lock inventory rows in ascending product order.
                $rows = Inventory::where('branch_id', $branch->id)->whereIn('product_id', array_keys($lines))
                    ->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

                // 6-7. Re-check stock and compute totals from DB prices.
                $subtotal = 0;
                $computed = [];
                $errors = [];
                foreach ($lines as $pid => $qty) {
                    $product = $products->get($pid);
                    if (! $product) {
                        throw new BusinessRuleException('A product in the cart is no longer available. Please remove it and try again.');
                    }
                    if (! $product->unit->allowsDecimal() && $qty % 1000 !== 0) {
                        throw new BusinessRuleException("{$product->name} can only be sold in whole {$product->unit->short()}.");
                    }
                    $available = Money::qtyToMilli($rows->get($pid)?->quantity ?? '0');
                    if ($qty > $available) {
                        $errors[$pid] = "Insufficient stock for {$product->name}: requested ".Money::qty(Money::qtyFromMilli($qty)).', available '.Money::qty(Money::qtyFromMilli($available)).'.';

                        continue;
                    }
                    $price = Money::toMinor($product->price);
                    $line = Money::lineTotal($qty, $price);
                    $subtotal += $line;
                    $computed[$pid] = compact('product', 'qty', 'price', 'line');
                }
                if ($errors) {
                    throw new BusinessRuleException(implode(' ', $errors), $errors);
                }

                [$discountType, $discountValue, $discount] = $this->discount($payment, $subtotal, $settings->max_discount_percent);
                $taxRate = $settings->tax_enabled ? Money::toMinor($settings->tax_rate) : 0; // hundredths of a percent
                $tax = Money::percentOf($subtotal - $discount, $taxRate);
                $total = $subtotal - $discount + $tax;

                $tendered = Money::toMinor((string) ($payment['amount_tendered'] ?? '0'));
                if ($tendered < $total) {
                    throw new BusinessRuleException('Cash received ('.Money::format(Money::fromMinor($tendered)).') is less than the total ('.Money::format(Money::fromMinor($total)).').');
                }

                // 8-9. Invoice number + sale header.
                $sale = Sale::create([
                    'shop_id' => $branch->shop_id, 'branch_id' => $branch->id, 'cashier_id' => $cashier->id,
                    'invoice_no' => $this->numbers->next($branch, $settings->invoice_prefix ?: 'INV'),
                    'idempotency_key' => $key,
                    'customer_name' => $payment['customer_name'] ?? null,
                    'subtotal' => Money::fromMinor($subtotal),
                    'discount_type' => $discount > 0 ? $discountType : null,
                    'discount_value' => $discount > 0 ? Money::fromMinor($discountValue) : '0.00',
                    'discount_amount' => Money::fromMinor($discount),
                    'tax_rate' => Money::fromMinor($taxRate),
                    'tax_amount' => Money::fromMinor($tax),
                    'total' => Money::fromMinor($total),
                    'status' => SaleStatus::Completed,
                    'notes' => $payment['notes'] ?? null,
                    'sold_at' => now(),
                ]);

                // 10, 12, 13. Items (with snapshots), stock deduction, movements.
                foreach ($computed as $pid => $c) {
                    $sale->items()->create([
                        'product_id' => $pid, 'product_name' => $c['product']->name, 'sku' => $c['product']->sku,
                        'unit' => $c['product']->unit->value, 'quantity' => Money::qtyFromMilli($c['qty']),
                        'unit_price' => Money::fromMinor($c['price']), 'cost_price' => $c['product']->cost_price,
                        'line_total' => Money::fromMinor($c['line']),
                    ]);
                    $row = $rows->get($pid);
                    $row->setRelation('product', $c['product']);
                    $this->inventory->apply($row, -$c['qty'], MovementType::Sale, $cashier, 'Invoice '.$sale->invoice_no, $sale);
                }

                // 11. Payment.
                $sale->payment()->create([
                    'method' => 'cash', 'amount' => Money::fromMinor($total),
                    'amount_tendered' => Money::fromMinor($tendered), 'change_due' => Money::fromMinor($tendered - $total),
                ]);

                // 14. Audit.
                $this->logger->log('sale.created', "Invoice {$sale->invoice_no} created for ".Money::format($sale->total), $sale,
                    ['items' => count($computed), 'total' => $sale->total], $cashier);

                return $sale;
            }, 3); // 15. commit
        } catch (QueryException $e) {
            // Concurrent duplicate submission with same idempotency key.
            if (($e->errorInfo[1] ?? null) === 1062 && ($existing = $this->findExisting($cashier, $key))) {
                return $existing;
            }
            throw $e;
        }
    }

    private function findExisting(User $cashier, string $key): ?Sale
    {
        $sale = Sale::where('idempotency_key', $key)->first();
        if ($sale && $sale->cashier_id !== $cashier->id) {
            throw new BusinessRuleException('Invalid checkout token. Please refresh the POS.');
        }

        return $sale;
    }

    /** @return array{0:?string,1:int,2:int} [type, value(minor), discount(minor)] */
    private function discount(array $payment, int $subtotal, string $maxPercent): array
    {
        $type = $payment['discount_type'] ?? null;
        $value = Money::toMinor((string) ($payment['discount_value'] ?? '0'));
        if (! $type || $value <= 0) {
            return [null, 0, 0];
        }
        if ($type === 'percent') {
            if ($value > Money::toMinor($maxPercent)) {
                throw new BusinessRuleException("Discount cannot exceed {$maxPercent}%.");
            }

            return ['percent', $value, Money::percentOf($subtotal, $value)];
        }
        if ($type === 'fixed') {
            if ($value > $subtotal) {
                throw new BusinessRuleException('Discount cannot be larger than the subtotal.');
            }
            if ($subtotal > 0 && $value * 10000 > $subtotal * Money::toMinor($maxPercent)) {
                throw new BusinessRuleException("Discount cannot exceed {$maxPercent}% of the subtotal.");
            }

            return ['fixed', $value, $value];
        }
        throw new BusinessRuleException('Invalid discount type.');
    }
}
