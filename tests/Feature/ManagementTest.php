<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** KS-T03/T04 product & category management, branch and employee management. */
class ManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_crud_with_opening_stock_and_image(): void
    {
        Storage::fake('public');
        $f = $this->makeShop();
        $this->actingAs($f->manager)->post('/products', [
            'name' => 'Sugar', 'sku' => 'sug-1', 'category_id' => $f->cat->id, 'unit' => 'kg', 'price' => '155.50', 'is_active' => 1,
            'opening' => [$f->b1->id => '40.5'], 'image' => UploadedFile::fake()->image('s.png'),
        ])->assertRedirect();
        $p = Product::where('sku', 'SUG-1')->firstOrFail();
        $this->assertNotNull($p->image_path);
        Storage::disk('public')->assertExists($p->image_path);
        $this->assertDatabaseHas('inventory', ['product_id' => $p->id, 'branch_id' => $f->b1->id, 'quantity' => '40.500']);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $p->id, 'type' => 'opening']);

        $this->actingAs($f->manager)->put("/products/{$p->id}", ['name' => 'Sugar Fine', 'sku' => 'SUG-1', 'category_id' => $f->cat->id, 'unit' => 'kg', 'price' => '160', 'is_active' => 1])->assertRedirect();
        $this->assertSame('160.00', $p->fresh()->price);
        $this->actingAs($f->manager)->delete("/products/{$p->id}")->assertRedirect();
        $this->assertSoftDeleted($p);
        $this->actingAs($f->manager)->patch("/products/{$p->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($p);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.archived']);
    }

    public function test_product_validation_and_duplicate_sku(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->admin)->post('/products', ['name' => '', 'sku' => 'rice', 'category_id' => $f->cat->id, 'unit' => 'ton', 'price' => '-1'])
            ->assertSessionHasErrors(['name', 'sku', 'unit', 'price']);
        // same SKU allowed in another shop
        $o = $this->makeShop('Other');
        $this->actingAs($o->admin)->post('/products', ['name' => 'X', 'sku' => 'NEW1', 'category_id' => $f->cat->id, 'unit' => 'pcs', 'price' => '5'])->assertSessionHasErrors('category_id');
    }

    public function test_sold_product_keeps_history_when_archived(): void
    {
        $f = $this->makeShop();
        $sale = $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
        $this->actingAs($f->admin)->delete("/products/{$f->tea->id}");
        $this->assertDatabaseHas('products', ['id' => $f->tea->id]);
        $this->actingAs($f->admin)->get("/sales/{$sale->id}")->assertOk()->assertSee('Tea');
    }

    public function test_category_crud_duplicates_and_archive_rules(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->manager)->post('/categories', ['name' => 'Spices', 'is_active' => 1])->assertRedirect();
        $this->actingAs($f->manager)->post('/categories', ['name' => 'Spices'])->assertSessionHasErrors('name');
        $c = Category::where('name', 'Spices')->first();
        $this->actingAs($f->manager)->put("/categories/{$c->id}", ['name' => 'Masala', 'is_active' => 1])->assertRedirect();
        $this->actingAs($f->manager)->delete("/categories/{$c->id}")->assertRedirect();
        $this->assertSoftDeleted($c);
        $this->actingAs($f->manager)->delete("/categories/{$f->cat->id}")->assertSessionHas('error'); // has active products
        $this->assertNotSoftDeleted($f->cat);
    }

    public function test_branch_crud_and_manager_assignment(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->admin)->post('/branches', ['name' => 'Gulberg', 'code' => 'glb', 'manager_id' => $f->manager->id, 'staff' => [$f->cashier->id], 'is_active' => 1])->assertRedirect();
        $b = Branch::where('code', 'GLB')->firstOrFail();
        $this->assertTrue($f->manager->fresh()->canAccessBranch($b));
        $this->assertTrue($b->users()->whereKey($f->cashier->id)->exists());
        $this->actingAs($f->admin)->post('/branches', ['name' => 'Gulberg', 'code' => 'GLB'])->assertSessionHasErrors(['name', 'code']);
        $this->actingAs($f->admin)->patch("/branches/{$b->id}/status")->assertRedirect();
        $this->assertFalse($b->fresh()->is_active);
        // inactive branch cannot sell
        $this->expectException(\App\Exceptions\BusinessRuleException::class);
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']], [], $f->cashier, $b->fresh());
    }

    public function test_employee_management_and_password_reset(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->admin)->post('/employees', ['name' => 'Zara', 'email' => 'zara@t.test', 'role' => 'cashier', 'branches' => [$f->b2->id],
            'password' => 'Secret123', 'password_confirmation' => 'Secret123', 'is_active' => 1])->assertRedirect();
        $z = User::where('email', 'zara@t.test')->firstOrFail();
        $this->assertSame($f->shop->id, $z->shop_id);
        $this->assertSame([$f->b2->id], $z->accessibleBranchIds());
        // cannot escalate to shop_admin / super_admin
        $this->actingAs($f->admin)->put("/employees/{$z->id}", ['name' => 'Zara', 'email' => 'zara@t.test', 'role' => 'super_admin', 'branches' => [$f->b2->id]])->assertSessionHasErrors('role');
        $this->actingAs($f->admin)->get("/employees/{$f->admin->id}/edit")->assertForbidden();
        $this->actingAs($f->admin)->post("/employees/{$z->id}/password")->assertSessionHas('credentials');
        $this->actingAs($f->admin)->patch("/employees/{$z->id}/status");
        $this->assertFalse($z->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'employee.password_reset']);
    }

    public function test_settings_update(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->admin)->put('/settings', ['name' => 'New Name', 'owner_name' => 'O', 'currency' => 'PKR', 'invoice_prefix' => 'ks', 'tax_enabled' => 1,
            'tax_rate' => '5', 'default_low_stock_threshold' => '3', 'max_discount_percent' => '15', 'receipt_footer' => 'Shukriya'])->assertRedirect();
        $this->assertSame('KS', $f->shop->fresh()->settings->invoice_prefix);
        $this->assertStringStartsWith('KS-BA-', $this->sell($f->fresh ?? $f, [['product_id' => $f->tea->id, 'quantity' => '1']], [], null, $f->b1->fresh())->invoice_no);
    }
}
