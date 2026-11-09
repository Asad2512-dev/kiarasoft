<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Controlled sales returns. The original sale is never modified except for
 * its returned_quantity counters, refunded_total and status.
 *
 * Refund per line = returned qty x unit price, reduced proportionally by the
 * sale's discount and increased by its tax, so refunds never exceed what was paid.
 */
class ReturnService
{
    public function __construct(
        private InventoryService $inventory,
        private InvoiceNumberGenerator $numbers,
        private ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, string>  $quantities  sale_item_id => quantity to return
     */
    public function process(User $user, Sale $sale, array $quantities, string $reason, bool $restock = true): SaleReturn
    {
        if (! $user->hasPermission('returns.create') || $user->shop_id !== $sale->shop_id || ! $user->canAccessBranch($sale->branch_id)) {
            throw new BusinessRuleException('You are not allowed to process returns for this invoice.');
        }
        $quantities = array_filter(array_map(fn ($q) => Money::qtyToMilli((string) ($q ?: '0')), $quantities), fn ($q) => $q !== 0);
        if (! $quantities) {
            throw new BusinessRuleException('Select at least one item and quantity to return.');
        }
        if (min($quantities) < 0) {
            throw new BusinessRuleException('Return quantities cannot be negative.');
        }

        return DB::transaction(function () use ($user, $sale, $quantities, $reason, $restock) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $items = SaleItem::where('sale_id', $sale->id)->whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($items->count() !== count($quantities)) {
                throw new BusinessRuleException('One or more items do not belong to this invoice.');
            }

            $subtotal = Money::toMinor($sale->subtotal);
            $netPaid = Money::toMinor($sale->total);
            $alreadyRefunded = Money::toMinor($sale->refunded_total);
            $lines = [];
            $refund = 0;
            foreach ($quantities as $itemId => $qty) {
                $item = $items[$itemId];
                $returnable = Money::qtyToMilli($item->quantity) - Money::qtyToMilli($item->returned_quantity);
                if ($qty > $returnable) {
                    throw new BusinessRuleException("Cannot return more {$item->product_name} than was sold. Returnable: ".Money::qty(Money::qtyFromMilli($returnable)).'.');
                }
                if (! in_array($item->unit, ['kg', 'g', 'l', 'ml'], true) && $qty % 1000 !== 0) {
                    throw new BusinessRuleException("{$item->product_name} can only be returned in whole units.");
                }
                $gross = Money::lineTotal($qty, Money::toMinor($item->unit_price));
                // share of the amount actually paid (accounts for discount & tax)
                $amount = $subtotal > 0 ? Money::divRound($gross * $netPaid, $subtotal) : 0;
                $refund += $amount;
                $lines[] = compact('item', 'qty', 'amount');
            }

            // Rounding guards: the final return refunds exactly the remaining paid amount,
            // and no return can ever exceed it.
            $completes = SaleItem::where('sale_id', $sale->id)->get()->every(function ($i) use ($quantities) {
                return Money::qtyToMilli($i->quantity) - Money::qtyToMilli($i->returned_quantity) - ($quantities[$i->id] ?? 0) <= 0;
            });
            $refund = $completes ? $netPaid - $alreadyRefunded : min($refund, $netPaid - $alreadyRefunded);
            if ($refund < 0) {
                throw new BusinessRuleException('This invoice has already been fully refunded.');
            }

            $return = SaleReturn::create([
                'shop_id' => $sale->shop_id, 'branch_id' => $sale->branch_id, 'sale_id' => $sale->id, 'user_id' => $user->id,
                'return_no' => $this->numbers->next($sale->branch, 'RET'),
                'refund_amount' => Money::fromMinor($refund), 'reason' => mb_substr($reason, 0, 255),
            ]);

            $branch = $sale->branch;
            foreach ($lines as $l) {
                $return->items()->create([
                    'sale_item_id' => $l['item']->id, 'product_id' => $l['item']->product_id,
                    'quantity' => Money::qtyFromMilli($l['qty']), 'amount' => Money::fromMinor($l['amount']), 'restocked' => $restock,
                ]);
                $l['item']->returned_quantity = Money::qtyFromMilli(Money::qtyToMilli($l['item']->returned_quantity) + $l['qty']);
                $l['item']->save();
                if ($restock) {
                    $inv = $this->inventory->lockRow($branch, $l['item']->product()->first());
                    $this->inventory->apply($inv, $l['qty'], MovementType::SaleReturn, $user, "Return {$return->return_no} ({$sale->invoice_no})", $return);
                }
            }

            $allReturned = SaleItem::where('sale_id', $sale->id)->get()
                ->every(fn ($i) => Money::qtyToMilli($i->returned_quantity) >= Money::qtyToMilli($i->quantity));
            $sale->refunded_total = Money::fromMinor($alreadyRefunded + $refund);
            $sale->status = $allReturned ? SaleStatus::Returned : SaleStatus::PartiallyReturned;
            $sale->save();

            $this->logger->log('sale.returned', "Return {$return->return_no} on {$sale->invoice_no}: refund ".Money::format($return->refund_amount),
                $return, ['sale' => $sale->invoice_no, 'restock' => $restock, 'reason' => $reason], $user);

            return $return;
        });
    }
}
