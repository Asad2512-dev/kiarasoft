<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\Shop;
use App\Models\SignupRequest;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::create(['name' => 'Root', 'email' => 'root@t.test', 'role' => 'super_admin', 'password' => 'Password1']);
    }

    public function test_signup_and_approval_onboards_shop(): void
    {
        $this->post('/register-shop', ['shop_name' => 'Quetta Mart', 'owner_name' => 'Ali', 'email' => 'ali@q.test', 'phone' => '0300-1112223'])->assertRedirect(route('login'));
        $req = SignupRequest::firstOrFail();
        $this->actingAs($this->superAdmin())->post("/admin/signups/{$req->id}/approve")->assertSessionHas('credentials');
        $shop = Shop::where('name', 'Quetta Mart')->firstOrFail();
        $this->assertSame(1, $shop->branches()->count());
        $this->assertTrue(User::where('email', 'ali@q.test')->where('role', 'shop_admin')->exists());
    }

    public function test_suspending_shop_blocks_its_users(): void
    {
        $f = $this->makeShop();
        $this->actingAs($this->superAdmin())->patch("/admin/shops/{$f->shop->id}/status")->assertRedirect();
        $this->actingAs($f->cashier->fresh())->get('/pos')->assertRedirect(route('login'));
    }

    public function test_admin_pages_and_health(): void
    {
        $this->makeShop();
        $su = $this->superAdmin();
        foreach (['/admin', '/admin/shops', '/admin/signups', '/admin/users', '/admin/activity', '/admin/backups', '/admin/system'] as $u) {
            $this->actingAs($su)->get($u)->assertOk();
        }
        $this->artisan('kirasoft:health')->assertExitCode(0);
    }

    /** Backup + restore into a separate test database. */
    public function test_backup_and_restore_into_separate_database(): void
    {
        $f = $this->makeShop();
        $this->sell($f, [['product_id' => $f->tea->id, 'quantity' => '1']]);
        // RefreshDatabase keeps data in an open transaction; commit a snapshot visible to mysqldump.
        \DB::commit();
        $backup = app(BackupService::class)->create($this->superAdmin(), 'test');
        $this->assertGreaterThan(1000, $backup->size_bytes);
        $this->assertFileExists(storage_path('app/private/backups/'.$backup->filename));
        $counts = app(BackupService::class)->restore($backup, config('kirasoft.restore_test_database'));
        $this->assertSame(1, $counts['shops']);
        $this->assertSame(1, $counts['sales']);
        $this->assertSame(2, $counts['products']);
        Artisan::call('migrate:fresh');
        @unlink(storage_path('app/private/backups/'.$backup->filename));
        \DB::beginTransaction();
    }

    public function test_backups_are_not_publicly_reachable(): void
    {
        $this->assertContains($this->get('/storage/backups/x.sql')->status(), [403, 404]);
        $f = $this->makeShop();
        $this->actingAs($f->admin)->get('/admin/backups')->assertForbidden();
    }
}
