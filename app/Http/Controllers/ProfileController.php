<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()->load('branches')]);
    }

    public function update(Request $request, ActivityLogger $logger)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user->update($data);
        $logger->log('profile.updated', "{$user->name} updated their profile", $user, ActivityLogger::diff($user));

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request, ActivityLogger $logger)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user = $request->user();
        $user->update(['password' => $request->password]);
        Auth::logoutOtherDevices($request->password);
        $request->session()->regenerate();
        $logger->log('profile.password', "{$user->name} changed their password", $user);

        return back()->with('success', 'Password changed. Other sessions were signed out.');
    }
}
