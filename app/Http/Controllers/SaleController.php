<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        [$from, $to] = $this->dateRange($request, 'last30');

        $query = Sale::where('shop_id', $user->shop_id)->whereIn('branch_id', $branchIds ?: [0])
            ->when(! $user->hasPermission('sales.view_all'), fn ($q) => $q->where('cashier_id', $user->id))
            ->when($user->hasPermission('sales.view_all') && $request->integer('cashier'), fn ($q, $c) => $q->where('cashier_id', $c))
            ->when(SaleStatus::tryFrom((string) $request->status), fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('invoice_no', 'like', "%{$s}%")->orWhere('customer_name', 'like', "%{$s}%")))
            ->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);

        $totals = (clone $query)->toBase()->selectRaw('COUNT(*) as c, COALESCE(SUM(total),0) as t, COALESCE(SUM(refunded_total),0) as r')->first();
        $sales = $query->with(['branch', 'cashier'])->withCount('items')->latest('sold_at')->paginate(20)->withQueryString();
        $branches = $user->accessibleBranches()->get();
        $cashiers = $user->hasPermission('sales.view_all')
            ? User::where('shop_id', $user->shop_id)->whereIn('role', ['cashier', 'branch_manager', 'shop_admin'])->orderBy('name')->get(['id', 'name']) : collect();

        return view('sales.index', compact('sales', 'branches', 'cashiers', 'from', 'to', 'totals'));
    }

    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);
        $sale->load(['items', 'payment', 'branch', 'cashier', 'returns.items.saleItem', 'returns.user']);

        return view('sales.show', compact('sale'));
    }

    public function receipt(Request $request, Sale $sale)
    {
        $this->authorize('view', $sale);
        $sale->load(['items', 'payment', 'branch', 'cashier', 'shop']);

        return view('sales.receipt', ['sale' => $sale, 'settings' => $sale->shop->config(), 'autoprint' => $request->boolean('print')]);
    }
}
