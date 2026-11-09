<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every screen renders (HTTP 200) for the roles allowed to see it. */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_shop_pages_render_for_each_role(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '2']]);
        $ret = app(\App\Services\ReturnService::class)->process($f->admin, $sale, [$sale->items()->first()->id => '1'], 'x');
        $admin = ['/dashboard', '/pos', '/sales', "/sales/{$sale->id}", "/sales/{$sale->id}/receipt", "/sales/{$sale->id}/return", '/returns', "/returns/{$ret->id}",
            '/products', '/products/create', "/products/{$f->tea->id}", "/products/{$f->tea->id}/edit", '/products/export', '/categories', '/inventory', '/inventory/movements',
            '/inventory/adjust', '/inventory/reconcile', '/alerts', '/reports', '/branches', '/branches/create', "/branches/{$f->b1->id}", "/branches/{$f->b1->id}/edit",
            '/employees', '/employees/create', "/employees/{$f->cashier->id}/edit", '/activity', '/settings', '/profile'];
        foreach ($admin as $u) { $this->actingAs($f->admin)->get($u)->assertOk(); }
        foreach (['/dashboard', '/pos', '/sales', '/products', '/inventory', '/inventory/adjust', '/reports', '/branches', "/branches/{$f->b1->id}", '/employees', '/activity', '/returns'] as $u) {
            $this->actingAs($f->manager)->get($u)->assertOk();
        }
        foreach (['/pos', '/sales', "/sales/{$sale->id}", "/sales/{$sale->id}/receipt", '/alerts', '/inventory', '/inventory/movements', '/profile'] as $u) {
            $this->actingAs($f->cashier)->get($u)->assertOk();
        }
    }

    public function test_guest_pages(): void
    {
        foreach (['/login', '/forgot-password', '/register-shop', '/up'] as $u) { $this->get($u)->assertOk(); }
        $this->get('/login')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_landing_page_for_guests_and_redirect_for_users(): void
    {
        $this->get('/')->assertOk()->assertSee('Register your shop')->assertSee(route('login'), false);
        $f = $this->makeShop();
        $this->actingAs($f->cashier)->get('/')->assertRedirect(route('pos.index'));
        $this->actingAs($f->admin)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_output_is_xss_escaped(): void
    {
        $f = $this->makeShop();
        $f->tea->update(['name' => '<script>alert(1)</script>']);
        $this->actingAs($f->admin)->get('/products')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

}
