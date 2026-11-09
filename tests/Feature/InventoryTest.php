<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Services\AlertService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** KS-T10/T11: stock adjustments, movement integrity, low-stock alerts. */
class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_out_and_count_adjustment_record_movements(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b1->id, 'product_id' => $f->rice->id, 'type' => 'stock_in', 'quantity' => '5.5', 'reason' => 'Supplier'])->assertRedirect();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b1->id, 'product_id' => $f->rice->id, 'type' => 'stock_out', 'quantity' => '1.5', 'reason' => 'Damaged'])->assertRedirect();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b1->id, 'product_id' => $f->rice->id, 'type' => 'adjustment', 'quantity' => '23', 'reason' => 'Count'])->assertRedirect();
        $this->assertSame('23.000', Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->rice->id)->value('quantity'));
        $last = InventoryMovement::latest('id')->first();
        $this->assertSame('-1.000', $last->quantity_change);
        $this->assertSame('24.000', $last->quantity_before);
        $this->assertSame([], app(InventoryService::class)->reconcile($f->shop->id));
        $this->assertDatabaseHas('activity_logs', ['action' => 'inventory.adjustment']);
    }

    public function test_negative_stock_is_not_allowed(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b1->id, 'product_id' => $f->tea->id, 'type' => 'stock_out', 'quantity' => '11', 'reason' => 'x'])
            ->assertSessionHas('error');
        $this->assertSame('10.000', Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->tea->id)->value('quantity'));
    }

    public function test_reason_required_for_stock_out_and_opening_only_once(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->manager)->post('/inventory/adjust', ['branch_id' => $f->b1->id, 'product_id' => $f->tea->id, 'type' => 'stock_out', 'quantity' => '1'])->assertSessionHasErrors('reason');
        $this->expectException(BusinessRuleException::class);
        app(InventoryService::class)->adjust($f->admin, $f->b1, $f->tea, MovementType::Opening, '5', null);
    }

    public function test_sales_and_movements_always_reconcile(): void
    {
        $f = $this->makeShop();
        $this->sell($f, [['product_id' => $f->rice->id, 'quantity' => '3.25'], ['product_id' => $f->tea->id, 'quantity' => '4']]);
        $this->assertSame([], app(InventoryService::class)->reconcile($f->shop->id));
        $this->actingAs($f->admin)->get('/inventory/reconcile')->assertOk()->assertSee('reconcile');
    }

    public function test_low_and_out_of_stock_alerts_with_threshold_override(): void
    {
        $f = $this->makeShop(); // default threshold 10; tea has 10 => low
        $svc = app(AlertService::class);
        $c = $svc->counts($f->manager);
        $this->assertSame(1, $c['low']);
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '10']]);
        $this->assertSame(1, $svc->counts($f->manager)['out']);
        $inv = Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->rice->id)->first();
        $this->actingAs($f->manager)->put("/inventory/{$inv->id}/threshold", ['low_stock_threshold' => 25])->assertRedirect();
        $this->assertSame(1, $svc->counts($f->manager->fresh())['low']);
        $this->actingAs($f->manager)->get('/alerts?status=out')->assertOk()->assertSee('Tea');
    }
}
