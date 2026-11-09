<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $known = User::where('email', $credentials['email'])->first();
            if ($known) {
                $logger->log('auth.failed', 'Failed login attempt', $known, [], $known);
            }
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->is_active || ($user->shop_id && ! $user->shop?->isActive())) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Your account or shop is inactive. Please contact your administrator.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $logger->log('auth.login', "{$user->name} signed in", $user, [], $user);

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request, ActivityLogger $logger)
    {
        $user = $request->user();
        if ($user) {
            $logger->log('auth.logout', "{$user->name} signed out", $user, [], $user);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
