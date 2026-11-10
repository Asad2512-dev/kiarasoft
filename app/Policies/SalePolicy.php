<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $user->hasPermission('sales.view') || $sale->shop_id !== $user->shop_id || ! $user->canAccessBranch($sale->branch_id)) {
            return false;
        }

        // cashiers see only their own invoices
        return $user->hasPermission('sales.view_all') || $sale->cashier_id === $user->id;
    }

    public function refund(User $user, Sale $sale): bool
    {
        return $user->hasPermission('returns.create') && $this->view($user, $sale);
    }
}
