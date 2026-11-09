@extends('layouts.app', ['title' => 'Inventory'])
@section('content')
<div class="page-head">
    <div><h1>Inventory</h1><p>Branch-wise stock levels. Every change is recorded as a movement.</p></div>
    <div class="actions">
        @can('inventory.adjust')
            <a href="{{ route('inventory.reconcile', request()->only('branch')) }}" class="btn btn-white"><i class="bi bi-clipboard-check"></i> Reconcile</a>
            <a href="{{ route('inventory.create') }}" class="btn btn-primary"><i class="bi bi-plus-slash-minus"></i> Adjust stock</a>
        @endcan
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4"><x-stat label="Stock lines" :value="number_format($value->line_count ?? 0)" icon="list-check" tint="blue"/></div>
    <div class="col-6 col-lg-4"><x-stat label="Retail value" :value="\App\Support\Money::format(round((float)($value->retail ?? 0), 2))" icon="cash-stack"/></div>
    <div class="col-12 col-lg-4"><x-stat label="Cost value" :value="\App\Support\Money::format(round((float)($value->cost ?? 0), 2))" icon="wallet2" tint="violet" foot="Products without a cost price count as zero"/></div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Product or SKU" aria-label="Search"></div>
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <select name="category" class="form-select" data-autosubmit aria-label="Category"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="status" class="form-select" data-autosubmit aria-label="Stock status"><option value="">Any status</option><option value="ok" @selected(request('status') === 'ok')>In stock</option><option value="low" @selected(request('status') === 'low')>Low stock</option><option value="out" @selected(request('status') === 'out')>Out of stock</option></select>
            <button class="btn btn-white" data-no-lock>Filter</button>
        </form>
    </div>
    @if($rows->isEmpty())
        <x-empty icon="boxes" title="No stock records" text="Record opening stock from a product page or the stock adjustment screen."/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Product</th><th>Branch</th><th class="text-end">Quantity</th><th class="text-end">Threshold</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($rows as $r)
            <tr>
                <td class="cell-primary"><span class="cell-title">{{ $r->product->name }}</span><div class="cell-sub">{{ $r->product->sku }} · {{ $r->product->category?->name }}</div></td>
                <td data-label="Branch">{{ $r->branch->name }}</td>
                <td data-label="Quantity" class="text-end tabular fw-semibold">@qty($r->quantity) {{ $r->product->unit->short() }}</td>
                <td data-label="Threshold" class="text-end tabular text-muted">@qty($r->effectiveThreshold($default))@if($r->low_stock_threshold !== null)<i class="bi bi-pin-angle-fill ms-1" title="Branch-specific threshold"></i>@endif</td>
                <td data-label="Status"><x-stock-badge :status="$r->stockStatus($default)"/></td>
                <td class="cell-actions text-end text-nowrap">
                    <a href="{{ route('inventory.movements', ['product' => $r->product_id, 'branch' => $r->branch_id]) }}" class="btn btn-sm btn-white btn-icon" title="Movement history" aria-label="Movement history"><i class="bi bi-clock-history"></i></a>
                    @can('inventory.adjust')
                    <div class="dropdown d-inline">
                        <button class="btn btn-sm btn-white btn-icon" data-bs-toggle="dropdown" aria-label="Set threshold" title="Set threshold"><i class="bi bi-sliders"></i></button>
                        <form method="POST" action="{{ route('inventory.threshold', $r) }}" class="dropdown-menu dropdown-menu-end p-3" style="width: 240px">
                            @csrf @method('PUT')
                            <label class="form-label small">Low-stock threshold at {{ $r->branch->name }}</label>
                            <input type="number" step="0.001" min="0" name="low_stock_threshold" value="{{ $r->low_stock_threshold !== null ? (float)$r->low_stock_threshold : '' }}" class="form-control form-control-sm mb-2" placeholder="Use product/shop default">
                            <button class="btn btn-primary btn-sm w-100">Save</button>
                        </form>
                    </div>
                    <a href="{{ route('inventory.create', ['product' => $r->product_id, 'branch' => $r->branch_id]) }}" class="btn btn-sm btn-white">Adjust</a>
                    @endcan
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
