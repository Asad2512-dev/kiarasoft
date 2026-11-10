<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Enums\Unit;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use App\Support\Csv;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $user->accessibleBranchIds();
        $sorts = ['name' => 'name', 'price' => 'price', 'sku' => 'sku', 'newest' => 'created_at'];
        $sort = $sorts[$request->sort] ?? 'name';

        $products = Product::with('category')->where('shop_id', $user->shop_id)
            ->withSum(['inventory as stock' => fn ($q) => $q->whereIn('branch_id', $branchIds ?: [0])], 'quantity')
            ->when($request->status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->integer('category'), fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('barcode', $s)))
            ->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->paginate(15)->withQueryString();
        $categories = Category::where('shop_id', $user->shop_id)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create(Request $request)
    {
        return view('products.form', $this->formData($request, new Product(['is_active' => true, 'unit' => Unit::Piece])));
    }

    public function store(Request $request, InventoryService $inventory, ActivityLogger $logger)
    {
        $this->authorize('create', Product::class);
        $data = $this->validated($request);
        $opening = $request->validate(['opening' => ['array'], 'opening.*' => ['nullable', 'numeric', 'min:0', 'max:9999999']])['opening'] ?? [];

        $product = DB::transaction(function () use ($request, $data, $opening, $inventory) {
            $product = Product::create($data + ['shop_id' => $request->user()->shop_id]);
            $this->storeImage($request, $product);
            foreach ($opening as $branchId => $qty) {
                if ($qty !== null && $qty !== '' && Money::qtyToMilli((string) $qty) > 0 && $request->user()->canAccessBranch((int) $branchId)) {
                    $inventory->adjust($request->user(), Branch::findOrFail($branchId), $product, MovementType::Opening, (string) $qty, 'Opening stock on product creation');
                }
            }

            return $product;
        });
        $logger->log('product.created', "Product {$product->name} ({$product->sku}) created", $product, ['price' => $product->price]);

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Request $request, Product $product)
    {
        $this->authorize('view', $product);
        $branchIds = $request->user()->accessibleBranchIds();
        $stock = Inventory::with('branch')->where('product_id', $product->id)->whereIn('branch_id', $branchIds ?: [0])->get();
        $movements = InventoryMovement::with(['branch', 'user'])->where('product_id', $product->id)->whereIn('branch_id', $branchIds ?: [0])->latest('id')->limit(15)->get();
        $sold = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sale_items.product_id', $product->id)
            ->whereIn('sales.branch_id', $branchIds ?: [0])->where('sales.sold_at', '>=', now()->subDays(30))
            ->selectRaw('COALESCE(SUM(sale_items.quantity - sale_items.returned_quantity),0) as qty, COALESCE(SUM(sale_items.line_total),0) as revenue')->first();
        $default = $request->user()->shop->config()->default_low_stock_threshold;

        return view('products.show', compact('product', 'stock', 'movements', 'sold', 'default'));
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        return view('products.form', $this->formData($request, $product));
    }

    public function update(Request $request, Product $product, ActivityLogger $logger)
    {
        $this->authorize('update', $product);
        $data = $this->validated($request, $product);
        if ($product->unit->value !== $data['unit'] && $product->saleItems()->exists() && ! Unit::from($data['unit'])->allowsDecimal() && $product->inventory()->whereRaw('quantity <> FLOOR(quantity)')->exists()) {
            return back()->withInput()->with('error', 'Cannot switch to a whole-number unit while fractional stock exists.');
        }
        $product->update($data);
        $this->storeImage($request, $product);
        $logger->log('product.updated', "Product {$product->name} updated", $product, ActivityLogger::diff($product));

        return redirect()->route('products.show', $product)->with('success', 'Product updated. Past invoices keep their original prices.');
    }

    public function archive(Product $product, ActivityLogger $logger)
    {
        $this->authorize('update', $product);
        $product->update(['is_active' => false]);
        $product->delete(); // soft delete: sales history keeps referencing it
        $logger->log('product.archived', "Product {$product->name} archived", $product);

        return redirect()->route('products.index')->with('success', "{$product->name} archived. It no longer appears in the POS; invoices are preserved.");
    }

    public function restore(Request $request, int $id, ActivityLogger $logger)
    {
        $product = Product::onlyTrashed()->where('shop_id', $request->user()->shop_id)->findOrFail($id);
        $this->authorize('update', $product);
        $product->restore();
        $product->update(['is_active' => true]);
        $logger->log('product.restored', "Product {$product->name} restored", $product);

        return back()->with('success', "{$product->name} restored.");
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $branchIds = $user->accessibleBranchIds();
        $products = Product::with('category')->where('shop_id', $user->shop_id)
            ->withSum(['inventory as stock' => fn ($q) => $q->whereIn('branch_id', $branchIds ?: [0])], 'quantity')->orderBy('name')->get();

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Name', 'Category', 'Unit', 'Price (PKR)', 'Cost (PKR)', 'Stock', 'Low-stock threshold', 'Status']);
            foreach ($products as $p) {
                fputcsv($out, array_map([Csv::class, 'safe'], [$p->sku, $p->name, $p->category?->name, $p->unit->value, $p->price, $p->cost_price, Money::qty($p->stock ?? 0), $p->low_stock_threshold, $p->is_active ? 'Active' : 'Inactive']));
            }
            fclose($out);
        }, 'products-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $shopId = $request->user()->shop_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_.]+$/', Rule::unique('products')->where('shop_id', $shopId)->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:60'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('shop_id', $shopId)->whereNull('deleted_at')],
            'unit' => ['required', new Enum(Unit::class)],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999.99', 'decimal:0,2'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,3'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('kirasoft.max_upload_kb')],
        ], ['sku.unique' => 'This SKU is already used by another product in your shop.', 'sku.regex' => 'SKU may contain letters, numbers, dashes, dots and underscores only.']);
        $data['sku'] = strtoupper($data['sku']);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request, Product $product): void
    {
        if ($request->hasFile('image')) {
            $old = $product->image_path;
            $product->update(['image_path' => $request->file('image')->store("products/{$product->shop_id}", 'public')]);
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        } elseif ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->update(['image_path' => null]);
        }
    }

    private function formData(Request $request, Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::where('shop_id', $request->user()->shop_id)->where('is_active', true)->orderBy('name')->get(),
            'branches' => $request->user()->accessibleBranches()->where('is_active', true)->get(),
            'units' => Unit::cases(),
        ];
    }
}
