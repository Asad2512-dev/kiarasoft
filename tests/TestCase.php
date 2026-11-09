<?php

namespace Tests;

use App\Enums\MovementType;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /** Each actingAs() starts a fresh session, as a real login would (needed with AuthenticateSession). */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if (app('auth')->guard($guard)->user()?->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            $this->flushSession();
        }

        return parent::actingAs($user, $guard);
    }

    /** Builds a shop with two branches, one user per role and stocked products. */
    protected function makeShop(string $name = 'Test Shop'): object
    {
        $shop = Shop::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::random(5), 'owner_name' => 'Owner', 'status' => 'active']);
        $shop->settings()->create(['max_discount_percent' => '50']);
        $b1 = Branch::create(['shop_id' => $shop->id, 'name' => 'Branch A', 'code' => 'BA']);
        $b2 = Branch::create(['shop_id' => $shop->id, 'name' => 'Branch B', 'code' => 'BB']);
        $admin = User::create(['shop_id' => $shop->id, 'name' => 'Admin', 'email' => 'admin.'.Str::random(6).'@t.test', 'role' => Role::ShopAdmin, 'password' => 'Password1']);
        $manager = User::create(['shop_id' => $shop->id, 'name' => 'Manager', 'email' => 'mgr.'.Str::random(6).'@t.test', 'role' => Role::BranchManager, 'password' => 'Password1']);
        $cashier = User::create(['shop_id' => $shop->id, 'name' => 'Cashier', 'email' => 'cash.'.Str::random(6).'@t.test', 'role' => Role::Cashier, 'password' => 'Password1']);
        $cashierB = User::create(['shop_id' => $shop->id, 'name' => 'Cashier B', 'email' => 'cashb.'.Str::random(6).'@t.test', 'role' => Role::Cashier, 'password' => 'Password1']);
        $manager->branches()->attach($b1->id);
        $cashier->branches()->attach($b1->id);
        $cashierB->branches()->attach($b2->id);
        $cat = Category::create(['shop_id' => $shop->id, 'name' => 'Groceries']);
        $rice = Product::create(['shop_id' => $shop->id, 'category_id' => $cat->id, 'name' => 'Rice', 'sku' => 'RICE', 'unit' => 'kg', 'price' => '350.00', 'cost_price' => '300.00']);
        $tea = Product::create(['shop_id' => $shop->id, 'category_id' => $cat->id, 'name' => 'Tea', 'sku' => 'TEA', 'unit' => 'pack', 'price' => '199.99']);
        $inv = app(InventoryService::class);
        foreach ([$b1, $b2] as $b) {
            $inv->adjust($admin, $b, $rice, MovementType::Opening, '20', 'open');
            $inv->adjust($admin, $b, $tea, MovementType::Opening, '10', 'open');
        }

        return (object) compact('shop', 'b1', 'b2', 'admin', 'manager', 'cashier', 'cashierB', 'cat', 'rice', 'tea');
    }

    protected function sell(object $f, array $items, array $extra = [], ?User $user = null, ?Branch $branch = null)
    {
        return app(CheckoutService::class)->checkout($user ?? $f->cashier, $branch ?? $f->b1, $items,
            $extra + ['idempotency_key' => (string) Str::uuid(), 'amount_tendered' => '100000']);
    }
}
