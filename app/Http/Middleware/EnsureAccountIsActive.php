<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Immediately ends sessions of deactivated users or users of suspended shops. */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (! $user->is_active || ($user->shop_id && ! $user->shop?->isActive()))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your account or shop has been deactivated. Please contact your administrator.']);
        }

        return $next($request);
    }
}
