<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Shop;
use App\Services\ActivityLogger;
use App\Services\ShopOnboardingService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->sort, ['name', 'created_at', 'city'], true) ? $request->sort : 'created_at';
        $shops = Shop::withCount(['branches', 'users'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('owner_name', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%")))
            ->when(in_array($request->status, ['active', 'suspended'], true), fn ($q) => $q->where('status', $request->status))
            ->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->paginate(15)->withQueryString();

        return view('admin.shops.index', compact('shops'));
    }

    public function create()
    {
        return view('admin.shops.form', ['shop' => new Shop]);
    }

    public function store(Request $request, ShopOnboardingService $onboarding)
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
        ]);
        $result = $onboarding->onboard($data, $request->user());

        return redirect()->route('admin.shops.show', $result['shop'])
            ->with('credentials', ['email' => $result['admin']->email, 'password' => $result['password']])
            ->with('success', 'Shop created. Share the temporary credentials securely with the owner.');
    }

    public function show(Shop $shop)
    {
        $shop->loadCount(['branches', 'users', 'products']);
        $branches = $shop->branches()->with('manager')->get();
        $users = $shop->users()->orderBy('role')->get();
        $salesTotal = Sale::where('shop_id', $shop->id)->sum('total');
        $salesCount = Sale::where('shop_id', $shop->id)->count();

        return view('admin.shops.show', compact('shop', 'branches', 'users', 'salesTotal', 'salesCount'));
    }

    public function edit(Shop $shop)
    {
        return view('admin.shops.form', compact('shop'));
    }

    public function update(Request $request, Shop $shop, ActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
        ]);
        $shop->update($data);
        $logger->log('shop.updated', "Shop {$shop->name} updated", $shop, ActivityLogger::diff($shop), null, $shop->id);

        return redirect()->route('admin.shops.show', $shop)->with('success', 'Shop details updated.');
    }

    public function toggle(Shop $shop, ActivityLogger $logger)
    {
        $shop->update(['status' => $shop->isActive() ? 'suspended' : 'active']);
        $logger->log('shop.status', "Shop {$shop->name} ".($shop->isActive() ? 'activated' : 'suspended'), $shop, [], null, $shop->id);

        return back()->with('success', "Shop {$shop->name} is now {$shop->status}.");
    }
}
