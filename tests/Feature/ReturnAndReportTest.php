<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Inventory;
use App\Services\ReportService;
use App\Services\ReturnService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** KS-T12 reports + returns module. */
class ReturnAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_return_restocks_and_updates_sale(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '4']]);
        $item = $sale->items()->first();
        $this->actingAs($f->manager)->post("/sales/{$sale->id}/return", ['quantities' => [$item->id => '1'], 'reason' => 'Damaged', 'restock' => 1])->assertRedirect();
        $sale->refresh();
        $this->assertSame('partially_returned', $sale->status->value);
        $this->assertSame('199.99', $sale->refunded_total);
        $this->assertSame('7.000', Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->tea->id)->value('quantity'));
        $this->assertDatabaseHas('inventory_movements', ['type' => 'return', 'quantity_change' => '1.000']);
    }

    public function test_excess_and_duplicate_refunds_are_rejected(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '2']]);
        $item = $sale->items()->first();
        $svc = app(ReturnService::class);
        try { $svc->process($f->manager, $sale, [$item->id => '3'], 'x'); $this->fail(); } catch (BusinessRuleException $e) { $this->assertStringContainsString('more', $e->getMessage()); }
        $svc->process($f->manager, $sale, [$item->id => '2'], 'x');
        try { $svc->process($f->manager, $sale->fresh(), [$item->id => '1'], 'x'); $this->fail(); } catch (BusinessRuleException) {}
        $this->assertSame('returned', $sale->fresh()->status->value);
        $this->assertSame('399.98', $sale->fresh()->refunded_total);
    }

    public function test_refund_respects_discount_and_never_exceeds_paid(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '3']], ['discount_type' => 'percent', 'discount_value' => '10']); // paid 539.97
        $item = $sale->items()->first();
        $svc = app(ReturnService::class);
        $r1 = $svc->process($f->manager, $sale, [$item->id => '1'], 'x');
        $r2 = $svc->process($f->manager, $sale->fresh(), [$item->id => '2'], 'x');
        $this->assertSame('179.99', $r1->refund_amount);
        $this->assertSame('539.97', number_format((float) $r1->refund_amount + (float) $r2->refund_amount, 2, '.', ''));
    }

    public function test_cashier_cannot_process_returns_and_not_restocked_option(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '2']]);
        $item = $sale->items()->first();
        $this->actingAs($f->cashier)->post("/sales/{$sale->id}/return", ['quantities' => [$item->id => '1'], 'reason' => 'x'])->assertForbidden();
        app(ReturnService::class)->process($f->admin, $sale, [$item->id => '1'], 'Expired', false);
        $this->assertSame('8.000', Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->tea->id)->value('quantity'));
    }

    public function test_report_totals_reconcile_with_database(): void
    {
        $f = $this->makeShop();
        $s1 = $this->sell($f, [['product_id' => $f->rice->id, 'quantity' => '1.5']], ['discount_type' => 'fixed', 'discount_value' => '25']);
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '2']], [], $f->cashierB, $f->b2);
        app(ReturnService::class)->process($f->admin, $s1, [$s1->items()->first()->id => '0.5'], 'x');
        $today = CarbonImmutable::today();
        $r = app(ReportService::class)->summary($f->shop->id, [$f->b1->id, $f->b2->id], $today, $today);
        $this->assertSame(2, $r['invoices']);
        $this->assertSame('924.98', $r['gross']);       // 525 + 399.98
        $this->assertSame('25.00', $r['discounts']);
        $this->assertSame(DB::table('sales')->sum('total'), $r['invoiced']);
        $this->assertSame(number_format(DB::table('sales')->sum('total') - DB::table('sale_returns')->sum('refund_amount'), 2, '.', ''), $r['net']);
        $branches = app(ReportService::class)->byBranch($f->shop->id, [$f->b1->id, $f->b2->id], $today, $today);
        $this->assertSame($r['net'], number_format(array_sum(array_map(fn ($b) => (float) $b['net'], $branches)), 2, '.', ''));
        $daily = app(ReportService::class)->daily($f->shop->id, [$f->b1->id, $f->b2->id], $today, $today);
        $this->assertSame($r['net'], $daily[0]['net']);
    }

    public function test_report_pages_and_csv_export(): void
    {
        $f = $this->makeShop();
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
        $this->actingAs($f->admin)->get('/reports?period=today')->assertOk()->assertSee('Net sales');
        $csv = $this->actingAs($f->admin)->get('/reports/export?period=today&type=daily')->assertOk()->streamedContent();
        $this->assertStringContainsString('199.99', $csv);
        $this->assertDatabaseHas('activity_logs', ['action' => 'report.exported']);
        foreach (['branch', 'cashier', 'products'] as $t) { $this->actingAs($f->admin)->get("/reports/export?type={$t}")->assertOk(); }
    }
}
