<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * In-app stock alerts computed live from inventory (no SMS/e-mail delivery).
 * Effective threshold = branch override -> product threshold -> shop default.
 */
class AlertService
{
    public function query(User $user, ?string $status = null, ?int $branchId = null)
    {
        $default = (string) ($user->shop?->config()->default_low_stock_threshold ?? '10');
        $threshold = 'COALESCE(inventory.low_stock_threshold, products.low_stock_threshold, '.DB::getPdo()->quote($default).')';
        $branchIds = $user->accessibleBranchIds(true);

        return Inventory::query()
            ->join('products', 'products.id', '=', 'inventory.product_id')
            ->join('branches', 'branches.id', '=', 'inventory.branch_id')
            ->where('inventory.shop_id', $user->shop_id)
            ->whereIn('inventory.branch_id', $branchIds ?: [0])
            ->when($branchId, fn ($q) => $q->where('inventory.branch_id', $branchId))
            ->whereNull('products.deleted_at')->where('products.is_active', true)
            ->when($status === 'out', fn ($q) => $q->where('inventory.quantity', '<=', 0))
            ->when($status === 'low', fn ($q) => $q->where('inventory.quantity', '>', 0)->whereRaw("inventory.quantity <= {$threshold}"))
            ->when(! $status, fn ($q) => $q->whereRaw("inventory.quantity <= {$threshold}"))
            ->select('inventory.*', 'products.name as product_name', 'products.sku', 'products.unit', 'branches.name as branch_name',
                DB::raw("{$threshold} as effective_threshold"))
            ->orderBy('inventory.quantity')->orderBy('products.name');
    }

    public function counts(User $user): array
    {
        if (! $user->shop_id) {
            return ['low' => 0, 'out' => 0, 'total' => 0];
        }
        $out = (clone $this->query($user, 'out'))->count();
        $low = (clone $this->query($user, 'low'))->count();

        return ['low' => $low, 'out' => $out, 'total' => $low + $out];
    }
}
