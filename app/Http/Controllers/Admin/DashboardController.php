<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\SignupRequest;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'shops' => Shop::count(),
            'active_shops' => Shop::where('status', 'active')->count(),
            'branches' => Branch::count(),
            'users' => User::where('role', '!=', 'super_admin')->count(),
            'pending' => SignupRequest::where('status', 'pending')->count(),
            'sales_today' => Sale::whereDate('sold_at', today())->count(),
        ];
        $growth = Shop::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as m, COUNT(*) as c")->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('m')->pluck('c', 'm');
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $chart = ['labels' => $months->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y'))->values(), 'data' => $months->map(fn ($m) => (int) ($growth[$m] ?? 0))->values()];

        $shops = Shop::withCount(['branches', 'users'])->latest()->limit(6)->get();
        $signups = SignupRequest::where('status', 'pending')->latest()->limit(5)->get();
        $logs = ActivityLog::with(['user', 'shop'])->latest('id')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'shops', 'signups', 'logs', 'chart'));
    }
}
