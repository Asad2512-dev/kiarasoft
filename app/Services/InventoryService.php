<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Every stock change in the system goes through this service so that
 * each one produces an auditable inventory_movements row.
 */
class InventoryService
{
    public function __construct(private ActivityLogger $logger) {}

    /**
     * Manual stock operation (opening / stock_in / stock_out / adjustment).
     * For "adjustment" the quantity is the new counted quantity (reconciliation);
     * for the others it is the positive amount moved.
     */
    public function adjust(User $user, Branch $branch, Product $product, MovementType $type, string $quantity, ?string $reason): InventoryMovement
    {
        if ($product->shop_id !== $branch->shop_id) {
            throw new BusinessRuleException('Product does not belong to this shop.');
        }
        $qty = Money::qtyToMilli($quantity);
        if ($qty < 0 || ($qty === 0 && $type !== MovementType::Adjustment)) {
            throw new BusinessRuleException('Quantity must be greater than zero.');
        }
        if (! $product->unit->allowsDecimal() && $qty % 1000 !== 0) {
            throw new BusinessRuleException("{$product->name} is sold in whole {$product->unit->short()} only.");
        }

        return DB::transaction(function () use ($user, $branch, $product, $type, $qty, $reason) {
            $inv = $this->lockRow($branch, $product);
            $before = Money::qtyToMilli($inv->quantity);

            $change = match ($type) {
                MovementType::Opening, MovementType::StockIn => $qty,
                MovementType::StockOut => -$qty,
                MovementType::Adjustment => $qty - $before,
                default => throw new BusinessRuleException('Unsupported manual movement type.'),
            };
            if ($type === MovementType::Opening && InventoryMovement::where('branch_id', $branch->id)->where('product_id', $product->id)->exists()) {
                throw new BusinessRuleException('Opening stock has already been recorded for this product at this branch. Use stock in or an adjustment instead.');
            }
            if ($type === MovementType::Adjustment && $change === 0) {
                throw new BusinessRuleException('Counted quantity equals current stock; nothing to adjust.');
            }

            $movement = $this->apply($inv, $change, $type, $user, $reason);
            $this->logger->log(
                'inventory.'.$type->value,
                "{$type->label()}: {$product->name} ".($change >= 0 ? '+' : '').Money::qty(Money::qtyFromMilli($change))." at {$branch->name}",
                $movement,
                ['before' => $movement->quantity_before, 'after' => $movement->quantity_after, 'reason' => $reason],
                $user,
            );

            return $movement;
        });
    }

    /** Lock (creating if necessary) the inventory row for update. Must be called inside a transaction. */
    public function lockRow(Branch $branch, Product $product): Inventory
    {
        Inventory::insertOrIgnore([
            'shop_id' => $branch->shop_id, 'branch_id' => $branch->id, 'product_id' => $product->id,
            'quantity' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Inventory::where('branch_id', $branch->id)->where('product_id', $product->id)->lockForUpdate()->firstOrFail();
    }

    /** Apply a signed milli-unit change to an already-locked row and write the movement. */
    public function apply(Inventory $inv, int $changeMilli, MovementType $type, ?User $user, ?string $reason, ?Model $reference = null): InventoryMovement
    {
        $before = Money::qtyToMilli($inv->quantity);
        $after = $before + $changeMilli;
        if ($after < 0) {
            $name = $inv->product?->name ?? 'product';
            throw new BusinessRuleException("Insufficient stock for {$name}. Available: ".Money::qty($inv->quantity).'.');
        }
        $inv->quantity = Money::qtyFromMilli($after);
        $inv->save();

        return InventoryMovement::create([
            'shop_id' => $inv->shop_id, 'branch_id' => $inv->branch_id, 'product_id' => $inv->product_id,
            'user_id' => $user?->id, 'type' => $type, 'quantity_change' => Money::qtyFromMilli($changeMilli),
            'quantity_before' => Money::qtyFromMilli($before), 'quantity_after' => Money::qtyFromMilli($after),
            'reason' => $reason ? mb_substr($reason, 0, 255) : null,
            'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(),
        ]);
    }

    /** Verify that stock levels equal the sum of their movements. Returns mismatching rows. */
    public function reconcile(int $shopId, ?int $branchId = null): array
    {
        $rows = DB::table('inventory as i')
            ->leftJoinSub(
                DB::table('inventory_movements')->select('branch_id', 'product_id', DB::raw('SUM(quantity_change) as moved'))->groupBy('branch_id', 'product_id'),
                'm', fn ($j) => $j->on('m.branch_id', '=', 'i.branch_id')->on('m.product_id', '=', 'i.product_id')
            )
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('branches as b', 'b.id', '=', 'i.branch_id')
            ->where('i.shop_id', $shopId)
            ->when($branchId, fn ($q) => $q->where('i.branch_id', $branchId))
            ->select('i.id', 'p.name as product', 'b.name as branch', 'i.quantity', DB::raw('COALESCE(m.moved, 0) as moved'))
            ->get();

        return $rows->filter(fn ($r) => Money::qtyToMilli($r->quantity) !== Money::qtyToMilli($r->moved))->values()->all();
    }
}
