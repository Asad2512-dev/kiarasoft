<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Creates a shop with default settings, a main branch and its Shop Admin account. */
class ShopOnboardingService
{
    public function __construct(private ActivityLogger $logger) {}

    /** @return array{shop: Shop, admin: User, password: string} */
    public function onboard(array $data, ?User $actor = null): array
    {
        return DB::transaction(function () use ($data, $actor) {
            $base = Str::slug($data['shop_name']) ?: 'shop';
            $slug = $base;
            for ($i = 2; Shop::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $shop = Shop::create([
                'name' => $data['shop_name'], 'slug' => $slug, 'owner_name' => $data['owner_name'],
                'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null, 'status' => 'active',
            ]);
            $shop->settings()->create(['receipt_header' => $shop->name, 'receipt_footer' => 'Thank you for shopping with us!']);
            $branch = $shop->branches()->create(['name' => 'Main Branch', 'code' => 'MAIN', 'address' => $data['address'] ?? null, 'phone' => $data['phone'] ?? null]);

            $password = Str::password(12, symbols: false);
            $admin = User::create([
                'shop_id' => $shop->id, 'name' => $data['owner_name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'role' => Role::ShopAdmin, 'password' => $password, 'is_active' => true,
            ]);
            $admin->branches()->attach($branch->id);

            $this->logger->log('shop.created', "Shop {$shop->name} onboarded", $shop, [], $actor, $shop->id);

            return compact('shop', 'admin', 'password');
        });
    }
}
