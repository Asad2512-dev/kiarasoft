<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        $default = $user->shop->config()->default_low_stock_threshold;
        $threshold = 'COALESCE(inventory.low_stock_threshold, products.low_stock_threshold, '.DB::getPdo()->quote((string) $default).')';

        $rows = Inventory::with(['product.category', 'branch'])
            ->join('products', 'products.id', '=', 'inventory.product_id')
            ->where('inventory.shop_id', $user->shop_id)->whereIn('inventory.branch_id', $branchIds ?: [0])
            ->whereNull('products.deleted_at')
            ->when($request->integer('category'), fn ($q, $c) => $q->where('products.category_id', $c))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('products.name', 'like', "%{$s}%")->orWhere('products.sku', 'like', "%{$s}%")))
            ->when($request->status === 'out', fn ($q) => $q->where('inventory.quantity', '<=', 0))
            ->when($request->status === 'low', fn ($q) => $q->where('inventory.quantity', '>', 0)->whereRaw("inventory.quantity <= {$threshold}"))
            ->when($request->status === 'ok', fn ($q) => $q->whereRaw("inventory.quantity > {$threshold}"))
            ->select('inventory.*')->orderBy('products.name')->paginate(20)->withQueryString();

        $branches = $user->accessibleBranches()->get();
        $categories = Category::where('shop_id', $user->shop_id)->orderBy('name')->get();
        $value = Inventory::join('products', 'products.id', '=', 'inventory.product_id')->where('inventory.shop_id', $user->shop_id)
            ->whereIn('inventory.branch_id', $branchIds ?: [0])->whereNull('products.deleted_at')
            ->selectRaw('SUM(inventory.quantity * products.price) as retail, SUM(inventory.quantity * COALESCE(products.cost_price,0)) as cost, COUNT(*) as line_count')->first();

        return view('inventory.index', compact('rows', 'branches', 'categories', 'default', 'value'));
    }

    public function movements(Request $request)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        [$from, $to] = $this->dateRange($request, 'last30');
        $movements = InventoryMovement::with(['product', 'branch', 'user'])->where('shop_id', $user->shop_id)->whereIn('branch_id', $branchIds ?: [0])
            ->when(MovementType::tryFrom((string) $request->type), fn ($q, $t) => $q->where('type', $t))
            ->when($request->integer('product'), fn ($q, $p) => $q->where('product_id', $p))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest('id')->paginate(25)->withQueryString();
        $branches = $user->accessibleBranches()->get();
        $product = $request->integer('product') ? Product::withTrashed()->where('shop_id', $user->shop_id)->find($request->integer('product')) : null;

        return view('inventory.movements', compact('movements', 'branches', 'from', 'to', 'product'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $branches = $user->accessibleBranches()->where('is_active', true)->get();
        $products = Product::where('shop_id', $user->shop_id)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit']);
        $stock = Inventory::whereIn('branch_id', $branches->pluck('id'))->get(['branch_id', 'product_id', 'quantity'])
            ->mapWithKeys(fn ($i) => [$i->branch_id.'-'.$i->product_id => $i->quantity]);

        return view('inventory.adjust', compact('branches', 'products', 'stock'));
    }

    public function store(Request $request, InventoryService $service)
    {
        $user = $request->user();
        $data = $request->validate([
            'branch_id' => ['required', Rule::in($user->accessibleBranchIds())],
            'product_id' => ['required', Rule::exists('products', 'id')->where('shop_id', $user->shop_id)->whereNull('deleted_at')],
            'type' => ['required', Rule::in(['opening', 'stock_in', 'stock_out', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0', 'max:9999999', 'decimal:0,3'],
            'reason' => [Rule::requiredIf(in_array($request->type, ['stock_out', 'adjustment'], true)), 'nullable', 'string', 'max:255'],
        ], ['reason.required' => 'Please give a reason for stock-outs and adjustments (e.g. damaged, expired, count correction).']);

        $branch = Branch::where('shop_id', $user->shop_id)->findOrFail($data['branch_id']);
        abort_unless($branch->is_active, 422, 'Branch is inactive.');
        $movement = $service->adjust($user, $branch, Product::findOrFail($data['product_id']), MovementType::from($data['type']), (string) $data['quantity'], $data['reason'] ?? null);

        return redirect()->route('inventory.create')->with('success', "Stock updated: {$movement->product->name} at {$branch->name} is now ".\App\Support\Money::qty($movement->quantity_after).' '.$movement->product->unit->short().'.');
    }

    public function threshold(Request $request, Inventory $inventory, ActivityLogger $logger)
    {
        abort_unless($inventory->shop_id === $request->user()->shop_id && $request->user()->canAccessBranch($inventory->branch_id), 403);
        $data = $request->validate(['low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999']]);
        $inventory->update(['low_stock_threshold' => $data['low_stock_threshold'] ?? null]);
        $logger->log('inventory.threshold', "Low-stock threshold for {$inventory->product->name} at {$inventory->branch->name} set to ".($data['low_stock_threshold'] ?? 'default'), $inventory);

        return back()->with('success', 'Threshold updated.');
    }

    public function reconcile(Request $request, InventoryService $service)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        $mismatches = collect($branchIds)->flatMap(fn ($id) => $service->reconcile($user->shop_id, $id));
        $checked = Inventory::whereIn('branch_id', $branchIds ?: [0])->count();

        return view('inventory.reconcile', ['mismatches' => $mismatches, 'checked' => $checked, 'branches' => $user->accessibleBranches()->get()]);
    }
}
