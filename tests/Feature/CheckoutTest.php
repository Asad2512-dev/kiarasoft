<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** KS-T05..T09: POS checkout, totals, stock deduction, invoices. */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function stock($f, $p, $b = null): string
    {
        return Inventory::where('branch_id', ($b ?? $f->b1)->id)->where('product_id', $p->id)->value('quantity');
    }

    public function test_checkout_creates_complete_sale_atomically(): void
    {
        $f = $this->makeShop();
        $key = (string) Str::uuid();
        $res = $this->actingAs($f->cashier)->postJson('/pos/checkout', [
            'branch_id' => $f->b1->id, 'idempotency_key' => $key, 'amount_tendered' => '2000',
            'items' => [['product_id' => $f->rice->id, 'quantity' => '2.5'], ['product_id' => $f->tea->id, 'quantity' => 3]],
        ])->assertOk()->json();

        $sale = Sale::with('items', 'payment')->find($res['sale']['id']);
        // 2.5 x 350 = 875.00 ; 3 x 199.99 = 599.97 ; total 1474.97
        $this->assertSame('1474.97', $sale->subtotal);
        $this->assertSame('1474.97', $sale->total);
        $this->assertSame('525.03', $sale->payment->change_due);
        $this->assertCount(2, $sale->items);
        $this->assertSame('Rice', $sale->items->firstWhere('sku', 'RICE')->product_name);
        $this->assertSame('17.500', $this->stock($f, $f->rice));
        $this->assertSame('7.000', $this->stock($f, $f->tea));
        $this->assertSame('20.000', $this->stock($f, $f->rice, $f->b2)); // other branch untouched
        $this->assertSame(2, InventoryMovement::where('reference_id', $sale->id)->where('type', 'sale')->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'sale.created', 'subject_id' => $sale->id]);
        $this->assertMatchesRegularExpression('/^INV-BA-\d{8}-0001$/', $sale->invoice_no);
    }

    public function test_client_prices_and_totals_are_ignored(): void
    {
        $f = $this->makeShop();
        $res = $this->actingAs($f->cashier)->postJson('/pos/checkout', [
            'branch_id' => $f->b1->id, 'idempotency_key' => (string) Str::uuid(), 'amount_tendered' => '1000',
            'total' => '1.00', 'items' => [['product_id' => $f->tea->id, 'quantity' => 1, 'price' => '0.01']],
        ])->assertOk();
        $this->assertSame('199.99', Sale::first()->total);
    }

    /** KS-T07 insufficient stock prevents checkout and nothing is saved */
    public function test_insufficient_stock_rolls_back_everything(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->cashier)->postJson('/pos/checkout', [
            'branch_id' => $f->b1->id, 'idempotency_key' => (string) Str::uuid(), 'amount_tendered' => '99999',
            'items' => [['product_id' => $f->rice->id, 'quantity' => 1], ['product_id' => $f->tea->id, 'quantity' => 11]],
        ])->assertStatus(422)->assertJsonPath("errors.{$f->tea->id}", fn ($m) => str_contains($m, 'Insufficient stock'));
        $this->assertSame(0, Sale::count());
        $this->assertSame('20.000', $this->stock($f, $f->rice));
        $this->assertSame(0, InventoryMovement::where('type', 'sale')->count());
    }

    public function test_decimal_quantities_and_whole_unit_rule(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->rice->id, 'quantity' => '0.333']]);
        $this->assertSame('116.55', $sale->total); // 0.333 x 350 = 116.55
        $this->expectException(BusinessRuleException::class);
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1.5']]);
    }

    public function test_percent_and_fixed_discounts_and_tax(): void
    {
        $f = $this->makeShop();
        $s1 = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '3']], ['discount_type' => 'percent', 'discount_value' => '10']);
        $this->assertSame('599.97', $s1->subtotal);
        $this->assertSame('60.00', $s1->discount_amount); // 59.997 -> 60.00
        $this->assertSame('539.97', $s1->total);

        $f->shop->settings->update(['tax_enabled' => true, 'tax_rate' => '17']);
        $f->b1->refresh();
        $s2 = $this->sell($f, [['product_id' => $f->rice->id, 'quantity' => '2']], ['discount_type' => 'fixed', 'discount_value' => '100']);
        // (700 - 100) * 17% = 102.00 ; total 702.00
        $this->assertSame('100.00', $s2->discount_amount);
        $this->assertSame('102.00', $s2->tax_amount);
        $this->assertSame('702.00', $s2->total);
    }

    public function test_excessive_discount_and_short_cash_are_rejected(): void
    {
        $f = $this->makeShop();
        try { $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], ['discount_type' => 'percent', 'discount_value' => '60']); $this->fail(); } catch (BusinessRuleException $e) { $this->assertStringContainsString('cannot exceed', $e->getMessage()); }
        try { $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], ['amount_tendered' => '100']); $this->fail(); } catch (BusinessRuleException $e) { $this->assertStringContainsString('less than the total', $e->getMessage()); }
        $this->assertSame(0, Sale::count());
    }

    /** Duplicate submission (double click / retry) creates exactly one sale */
    public function test_duplicate_checkout_is_idempotent(): void
    {
        $f = $this->makeShop();
        $payload = ['branch_id' => $f->b1->id, 'idempotency_key' => (string) Str::uuid(), 'amount_tendered' => '500', 'items' => [['product_id' => $f->tea->id, 'quantity' => 2]]];
        $a = $this->actingAs($f->cashier)->postJson('/pos/checkout', $payload)->json('sale.invoice_no');
        $b = $this->actingAs($f->cashier)->postJson('/pos/checkout', $payload)->json('sale.invoice_no');
        $this->assertSame($a, $b);
        $this->assertSame(1, Sale::count());
        $this->assertSame('8.000', $this->stock($f, $f->tea));
    }

    public function test_invoice_numbers_are_sequential_per_branch(): void
    {
        $f = $this->makeShop();
        $n = [];
        foreach (range(1, 3) as $i) { $n[] = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']])->invoice_no; }
        $nb = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], [], $f->cashierB, $f->b2)->invoice_no;
        $this->assertStringEndsWith('-0001', $n[0]);
        $this->assertStringEndsWith('-0003', $n[2]);
        $this->assertCount(3, array_unique($n));
        $this->assertStringStartsWith('INV-BB-', $nb);
        $this->assertStringEndsWith('-0001', $nb);
    }

    public function test_archived_or_inactive_products_cannot_be_sold(): void
    {
        $f = $this->makeShop();
        $f->tea->update(['is_active' => false]);
        $this->expectException(BusinessRuleException::class);
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
    }

    public function test_receipt_renders_with_snapshot_after_product_edit(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
        $f->tea->update(['name' => 'Renamed Tea', 'price' => '999.00']);
        $this->actingAs($f->cashier)->get("/sales/{$sale->id}/receipt")->assertOk()->assertSee('Tea')->assertSee('199.99')->assertDontSee('Renamed Tea');
        $this->assertSame('199.99', $sale->fresh()->total);
    }

    public function test_pos_catalog_endpoint_returns_branch_stock(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->cashier)->getJson("/pos/products?branch={$f->b1->id}")->assertOk()
            ->assertJsonFragment(['sku' => 'RICE', 'stock' => '20'])->assertJsonCount(2, 'products');
    }
}
