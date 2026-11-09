<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        [$from, $to] = $this->dateRange($request, 'last30');
        $returns = SaleReturn::with(['sale', 'branch', 'user'])->withCount('items')->where('shop_id', $user->shop_id)->whereIn('branch_id', $branchIds ?: [0])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('return_no', 'like', "%{$s}%")->orWhereHas('sale', fn ($x) => $x->where('invoice_no', 'like', "%{$s}%"))))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->latest()->paginate(20)->withQueryString();

        // invoice lookup to start a return
        $found = null;
        if ($request->filled('invoice')) {
            $found = Sale::where('shop_id', $user->shop_id)->whereIn('branch_id', $user->accessibleBranchIds() ?: [0])->where('invoice_no', trim($request->invoice))->first();
            if ($found) {
                return redirect()->route('returns.create', $found);
            }
            session()->now('error', 'No invoice found with that number in your branches.');
        }

        return view('returns.index', ['returns' => $returns, 'branches' => $user->accessibleBranches()->get(), 'from' => $from, 'to' => $to]);
    }

    public function create(Sale $sale)
    {
        $this->authorize('refund', $sale);
        $sale->load(['items', 'branch', 'cashier', 'returns']);

        return view('returns.create', compact('sale'));
    }

    public function store(Request $request, Sale $sale, ReturnService $service)
    {
        $this->authorize('refund', $sale);
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'reason' => ['required', 'string', 'max:255'],
            'restock' => ['boolean'],
        ]);
        $return = $service->process($request->user(), $sale, $data['quantities'], $data['reason'], $request->boolean('restock'));

        return redirect()->route('returns.show', $return)->with('success', "Return {$return->return_no} recorded. Refund ".\App\Support\Money::format($return->refund_amount).' to the customer.');
    }

    public function show(Request $request, SaleReturn $saleReturn)
    {
        abort_unless($saleReturn->shop_id === $request->user()->shop_id && $request->user()->canAccessBranch($saleReturn->branch_id), 403);
        $saleReturn->load(['items.saleItem', 'sale', 'branch', 'user']);

        return view('returns.show', ['return' => $saleReturn]);
    }
}
