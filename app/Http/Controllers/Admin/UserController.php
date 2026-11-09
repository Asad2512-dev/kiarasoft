<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/** Read-only overview of platform accounts (passwords are never visible). */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('shop')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->when(in_array($request->role, ['super_admin', 'shop_admin', 'branch_manager', 'cashier'], true), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }
}
