<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->user()->shop_id;
        $categories = Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->where('shop_id', $shopId)
            ->when($request->status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $this->authorize('create', Category::class);
        $category = Category::create($this->validated($request) + ['shop_id' => $request->user()->shop_id]);
        $logger->log('category.created', "Category {$category->name} created", $category);

        return back()->with('success', "Category {$category->name} added.");
    }

    public function update(Request $request, Category $category, ActivityLogger $logger)
    {
        $this->authorize('update', $category);
        $category->update($this->validated($request, $category));
        $logger->log('category.updated', "Category {$category->name} updated", $category, ActivityLogger::diff($category));

        return back()->with('success', 'Category updated.');
    }

    public function archive(Category $category, ActivityLogger $logger)
    {
        $this->authorize('update', $category);
        if ($category->products()->where('is_active', true)->exists()) {
            return back()->with('error', "Cannot archive {$category->name}: move or archive its active products first.");
        }
        $category->delete();
        $logger->log('category.archived', "Category {$category->name} archived", $category);

        return back()->with('success', "Category {$category->name} archived. Historical sales are unaffected.");
    }

    public function restore(Request $request, int $id, ActivityLogger $logger)
    {
        $category = Category::onlyTrashed()->where('shop_id', $request->user()->shop_id)->findOrFail($id);
        $this->authorize('update', $category);
        $category->restore();
        $logger->log('category.restored', "Category {$category->name} restored", $category);

        return back()->with('success', "Category {$category->name} restored.");
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories')->where('shop_id', $request->user()->shop_id)->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], ['name.unique' => 'A category with this name already exists (it may be archived).']);
        $data['name'] = trim($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
