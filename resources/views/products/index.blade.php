@extends('layouts.app', ['title' => 'Products'])
@section('content')
<div class="page-head">
    <div><h1>Products</h1><p>{{ number_format($products->total()) }} product(s) in your catalog.</p></div>
    <div class="actions">
        <a href="{{ route('products.export') }}" class="btn btn-white"><i class="bi bi-download"></i> Export CSV</a>
        <a href="{{ route('products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
    </div>
</div>
<div class="card table-card">
    <div class="card-header">
        <ul class="nav nav-tabs-clean w-100 mb-2">
            @foreach(['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $k => $v)
                <li class="nav-item"><a class="nav-link {{ request('status', '') === $k ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $k ?: null, 'page' => null]) }}">{{ $v }}</a></li>
            @endforeach
        </ul>
        <form class="filter-bar w-100" method="GET">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, SKU or barcode" aria-label="Search products"></div>
            <select name="category" class="form-select" data-autosubmit aria-label="Category"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="sort" class="form-select" data-autosubmit aria-label="Sort"><option value="name">Sort: Name</option><option value="price" @selected(request('sort') === 'price')>Sort: Price</option><option value="sku" @selected(request('sort') === 'sku')>Sort: SKU</option><option value="newest" @selected(request('sort') === 'newest')>Sort: Newest</option></select>
            <button class="btn btn-white" data-no-lock>Search</button>
        </form>
    </div>
    @if($products->isEmpty())
        <x-empty icon="box-seam" title="No products found" text="{{ request()->hasAny(['q','category','status']) ? 'Try clearing your filters.' : 'Add your first product to start selling.' }}"><a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add product</a></x-empty>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Product</th><th>Category</th><th class="text-end">Price</th><th class="text-end">Stock (your branches)</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($products as $p)
            <tr>
                <td class="cell-primary">
                    <div class="d-flex align-items-center gap-3">
                        @if($p->image_path)<img src="{{ $p->imageUrl() }}" alt="" class="product-thumb">@else<span class="product-thumb"><i class="bi bi-box"></i></span>@endif
                        <div class="min-w-0"><a href="{{ route('products.show', $p) }}" class="cell-title">{{ $p->name }}</a><div class="cell-sub mono">{{ $p->sku }} · {{ $p->unit->short() }}</div></div>
                    </div>
                </td>
                <td data-label="Category">{{ $p->category?->name }}</td>
                <td data-label="Price" class="text-end tabular fw-semibold">@money($p->price)</td>
                <td data-label="Stock" class="text-end tabular">@qty($p->stock ?? 0) {{ $p->unit->short() }}</td>
                <td data-label="Status">@if($p->trashed())<span class="badge-soft badge-soft-secondary">Archived</span>@else<x-active-badge :active="$p->is_active"/>@endif</td>
                <td class="cell-actions text-end text-nowrap">
                    @if($p->trashed())
                        <form method="POST" action="{{ route('products.restore', $p->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-soft-primary">Restore</button></form>
                    @else
                        <a href="{{ route('products.edit', $p) }}" class="btn btn-sm btn-white"><i class="bi bi-pencil"></i> Edit</a>
                    @endif
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $products->links() }}</div>
    @endif
</div>
@endsection
