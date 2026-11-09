<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AlertService;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports, AlertService $alerts)
    {
        $user = $request->user();
        $shopId = $user->shop_id;
        $branchIds = $this->branchScope($request);
        $today = CarbonImmutable::today();

        $todayStats = $reports->summary($shopId, $branchIds, $today, $today);
        $yesterday = $reports->summary($shopId, $branchIds, $today->subDay(), $today->subDay());
        $month = $reports->summary($shopId, $branchIds, $today->startOfMonth(), $today);
        $trend = $reports->daily($shopId, $branchIds, $today->subDays(13), $today);
        $branchComparison = $reports->byBranch($shopId, $branchIds, $today->startOfMonth(), $today);
        $topProducts = $reports->topProducts($shopId, $branchIds, $today->subDays(29), $today, 5);

        $stats = [
            'today' => $todayStats,
            'yesterday' => $yesterday,
            'month' => $month,
            'active_branches' => $user->accessibleBranches()->where('is_active', true)->count(),
            'products' => Product::where('shop_id', $shopId)->where('is_active', true)->count(),
            'alerts' => $alerts->counts($user),
        ];
        $recentSales = Sale::with(['branch', 'cashier'])->where('shop_id', $shopId)->whereIn('branch_id', $branchIds ?: [0])->latest('sold_at')->limit(7)->get();
        $lowStock = $alerts->query($user)->limit(6)->get();
        $activity = ActivityLog::with('user')->where('shop_id', $shopId)
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds ?: [0])->orWhereNull('branch_id'))
            ->latest('id')->limit(8)->get();
        $branches = $user->accessibleBranches()->get();

        return view('dashboard.index', compact('stats', 'trend', 'branchComparison', 'topProducts', 'recentSales', 'lowStock', 'activity', 'branches'));
    }
}
