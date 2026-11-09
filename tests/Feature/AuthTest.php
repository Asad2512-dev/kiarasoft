<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** KS-T01 valid login + role-based redirect */
    public function test_valid_login_redirects_by_role(): void
    {
        $f = $this->makeShop();
        $this->post('/login', ['email' => $f->cashier->email, 'password' => 'Password1'])->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($f->cashier);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'user_id' => $f->cashier->id]);
        $this->post('/logout');
        $this->post('/login', ['email' => $f->admin->email, 'password' => 'Password1'])->assertRedirect(route('dashboard'));
        $this->post('/logout');
        $super = User::create(['name' => 'S', 'email' => 's@t.test', 'role' => 'super_admin', 'password' => 'Password1']);
        $this->post('/login', ['email' => 's@t.test', 'password' => 'Password1'])->assertRedirect(route('admin.dashboard'));
    }

    /** KS-T02 invalid credentials */
    public function test_invalid_login_is_rejected(): void
    {
        $f = $this->makeShop();
        $this->post('/login', ['email' => $f->cashier->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.failed']);
        $this->assertStringNotContainsString('wrong', json_encode(ActivityLog::all()));
    }

    public function test_inactive_user_and_suspended_shop_cannot_login(): void
    {
        $f = $this->makeShop();
        $f->cashier->update(['is_active' => false]);
        $this->post('/login', ['email' => $f->cashier->email, 'password' => 'Password1'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $f->shop->update(['status' => 'suspended']);
        $this->post('/login', ['email' => $f->admin->email, 'password' => 'Password1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $f = $this->makeShop();
        $this->actingAs($f->cashier)->get('/pos')->assertOk();
        $f->cashier->update(['is_active' => false]);
        $this->actingAs($f->cashier->fresh())->get('/pos')->assertRedirect(route('login'));
    }

    public function test_login_is_rate_limited(): void
    {
        $f = $this->makeShop();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $f->cashier->email, 'password' => 'bad']);
        }
        $this->post('/login', ['email' => $f->cashier->email, 'password' => 'Password1'])->assertStatus(429);
    }

    public function test_session_regenerates_on_login_and_guests_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $f = $this->makeShop();
        $this->get('/login');
        $old = session()->getId();
        $this->post('/login', ['email' => $f->admin->email, 'password' => 'Password1']);
        $this->assertNotSame($old, session()->getId());
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $f = $this->makeShop();
        $this->post('/forgot-password', ['email' => $f->cashier->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($f->cashier, ResetPassword::class, function ($n) use (&$token) { $token = $n->token; return true; });
        $this->post('/reset-password', ['token' => $token, 'email' => $f->cashier->email, 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertRedirect(route('login'));
        $this->post('/login', ['email' => $f->cashier->email, 'password' => 'NewPass123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_unknown_email_reset_gives_same_response(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'nobody@t.test'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }
}
