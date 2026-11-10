<?php

namespace Tests\Feature;

use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** KS-T13/T14: role restrictions, shop isolation and branch isolation (enforced on the server). */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_reach_admin_modules(): void
    {
        $f = $this->makeShop();
        foreach (['/dashboard', '/products', '/products/create', '/categories', '/branches', '/employees', '/reports', '/reports/export', '/settings', '/activity', '/returns', '/inventory/adjust', '/admin', '/admin/backups'] as $url) {
            $this->actingAs($f->cashier)->get($url)->assertForbidden();
        }
        $this->actingAs($f->cashier)->post('/products', ['name' => 'X'])->assertForbidden();
    }

    public function test_manager_cannot_manage_branches_employees_or_settings(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->manager)->get('/branches/create')->assertForbidden();
        $this->actingAs($f->manager)->get('/employees/create')->assertForbidden();
        $this->actingAs($f->manager)->get('/settings')->assertForbidden();
        $this->actingAs($f->manager)->get('/products')->assertOk();
    }

    public function test_shop_admin_cannot_access_platform_admin(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->admin)->get('/admin/shops')->assertForbidden();
    }

    public function test_cross_shop_records_are_inaccessible(): void
    {
        $a = $this->makeShop('Shop A');
        $b = $this->makeShop('Shop B');
        $sale = $this->sell($b, [['product_id' => $b->tea->id, 'quantity' => '1']]);
        $u = $a->admin;
        $this->actingAs($u)->get("/products/{$b->rice->id}")->assertForbidden();
        $this->actingAs($u)->put("/products/{$b->rice->id}", ['name' => 'hack'])->assertForbidden();
        $this->actingAs($u)->get("/branches/{$b->b1->id}")->assertForbidden();
        $this->actingAs($u)->get("/sales/{$sale->id}")->assertForbidden();
        $this->actingAs($u)->get("/sales/{$sale->id}/receipt")->assertForbidden();
        $this->actingAs($u)->get("/employees/{$b->cashier->id}/edit")->assertForbidden();
        $this->actingAs($u)->get("/sales?branch={$b->b1->id}")->assertForbidden();
        $this->actingAs($u)->getJson("/pos/products?branch={$b->b1->id}")->assertNotFound();
        $this->actingAs($u)->postJson('/pos/checkout', ['branch_id' => $b->b1->id, 'idempotency_key' => (string) \Str::uuid(),
            'items' => [['product_id' => $b->tea->id, 'quantity' => 1]], 'amount_tendered' => 1000])->assertStatus(422);
        $this->assertSame(1, Sale::count());
        // product listing never shows the other shop's products
        $this->actingAs($u)->get('/products')->assertSee('RICE')->assertDontSee($b->shop->name);
    }

    public function test_cross_branch_access_is_denied(): void
    {
        $f = $this->makeShop();
        $saleB = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], [], $f->cashierB, $f->b2);
        // manager assigned only to branch A
        $this->actingAs($f->manager)->get("/sales/{$saleB->id}")->assertForbidden();
        $this->actingAs($f->manager)->get("/branches/{$f->b2->id}")->assertForbidden();
        $this->actingAs($f->manager)->get("/inventory?branch={$f->b2->id}")->assertForbidden();
        $this->actingAs($f->manager)->get("/reports?branch={$f->b2->id}")->assertForbidden();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b2->id, 'product_id' => $f->tea->id, 'type' => 'stock_in', 'quantity' => 5])->assertSessionHasErrors('branch_id');
        // cashier A cannot sell from branch B
        $this->actingAs($f->cashier)->postJson('/pos/checkout', ['branch_id' => $f->b2->id, 'idempotency_key' => (string) \Str::uuid(),
            'items' => [['product_id' => $f->tea->id, 'quantity' => 1]], 'amount_tendered' => 1000])->assertStatus(422);
        // shop admin sees all branches
        $this->actingAs($f->admin)->get("/sales/{$saleB->id}")->assertOk();
    }

    public function test_cashier_sees_only_own_sales(): void
    {
        $f = $this->makeShop();
        $mine = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
        $other = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], [], $f->manager);
        $this->actingAs($f->cashier)->get("/sales/{$mine->id}")->assertOk();
        $this->actingAs($f->cashier)->get("/sales/{$other->id}")->assertForbidden();
        $this->actingAs($f->cashier)->get('/sales')->assertSee($mine->invoice_no)->assertDontSee($other->invoice_no);
    }
}
