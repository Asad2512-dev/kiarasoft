<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = $user->accessibleBranches()->where('is_active', true)->get();
        $settings = $user->shop->config();
        $categories = Category::where('shop_id', $user->shop_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pos.index', [
            'branches' => $branches, 'settings' => $settings, 'categories' => $categories,
            'checkoutKey' => (string) Str::uuid(),
        ]);
    }

    /** Catalog snapshot for one branch (price + live stock). Prices are display-only; checkout re-reads them. */
    public function products(Request $request)
    {
        $user = $request->user();
        $branch = Branch::where('shop_id', $user->shop_id)->where('is_active', true)->findOrFail($request->integer('branch'));
        abort_unless($user->canAccessBranch($branch), 403);
        $default = (string) $user->shop->config()->default_low_stock_threshold;

        $products = Product::where('products.shop_id', $user->shop_id)->where('products.is_active', true)
            ->leftJoin('inventory', fn ($j) => $j->on('inventory.product_id', '=', 'products.id')->where('inventory.branch_id', $branch->id))
            ->orderBy('products.name')
            ->get(['products.id', 'products.name', 'products.sku', 'products.barcode', 'products.unit', 'products.price', 'products.category_id',
                'products.low_stock_threshold', 'products.image_path', 'inventory.quantity', 'inventory.low_stock_threshold as branch_threshold'])
            ->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode, 'unit' => $p->unit->short(),
                'decimal' => $p->unit->allowsDecimal(), 'price' => $p->price, 'category_id' => $p->category_id,
                'stock' => Money::qty($p->quantity ?? 0), 'low' => Money::qtyToMilli($p->quantity ?? '0') <= Money::qtyToMilli($p->branch_threshold ?? $p->low_stock_threshold ?? $default),
                'image' => $p->imageUrl(),
            ]);

        return response()->json(['products' => $products]);
    }

    public function checkout(Request $request, CheckoutService $checkout)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer'],
            'idempotency_key' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999', 'decimal:0,3'],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            'amount_tendered' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,2'],
            'customer_name' => ['nullable', 'string', 'max:120'],
        ], ['items.required' => 'The cart is empty.']);

        $branch = Branch::where('shop_id', $request->user()->shop_id)->find($data['branch_id']);
        if (! $branch) {
            throw new BusinessRuleException('You are not allowed to sell from this branch.');
        }
        $sale = $checkout->checkout($request->user(), $branch, $data['items'], $data);

        return response()->json([
            'message' => 'Sale completed',
            'sale' => [
                'id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'total' => $sale->total,
                'change_due' => $sale->payment->change_due, 'amount_tendered' => $sale->payment->amount_tendered,
            ],
            'receipt_url' => route('sales.receipt', $sale),
            'next_key' => (string) Str::uuid(),
        ]);
    }
}
