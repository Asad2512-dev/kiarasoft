<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.manage');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('products.manage') && $product->shop_id === $user->shop_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
