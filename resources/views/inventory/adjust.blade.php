@extends('layouts.app', ['title' => 'Stock adjustment'])
@section('content')
<div class="page-head"><div><h1>Stock adjustment</h1><p>Receive stock, remove damaged goods or correct counts. Each entry creates an auditable movement.</p></div></div>
<div class="row g-3" x-data="{
        branch: @js((int) old('branch_id', request('branch', $branches->first()?->id))),
        product: @js((int) old('product_id', request('product'))) || '',
        type: @js(old('type', 'stock_in')),
        qty: @js(old('quantity', '')),
        stock: @js($stock),
        products: @js($products->keyBy('id')->map(fn($p) => ['name' => $p->name, 'unit' => $p->unit->short(), 'decimal' => $p->unit->allowsDecimal()])),
        get current() { return parseFloat(this.stock[this.branch + '-' + this.product] ?? 0) },
        get unit() { return this.products[this.product]?.unit ?? '' },
        get after() { const q = parseFloat(this.qty || 0); return this.type === 'adjustment' ? q : (this.type === 'stock_out' ? this.current - q : this.current + q) },
        fmt(n) { return (Math.round(n * 1000) / 1000).toString() }
    }">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('inventory.store') }}" class="card">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Movement type</label>
                    <div class="row g-2" role="radiogroup">
                        @foreach(['stock_in' => ['box-arrow-in-down', 'Stock in', 'Received from supplier'], 'stock_out' => ['box-arrow-up', 'Stock out', 'Damaged, expired, used'], 'adjustment' => ['clipboard-check', 'Count correction', 'Set to counted quantity'], 'opening' => ['flag', 'Opening stock', 'First stock at a branch']] as $k => [$ic, $l, $h])
                            <div class="col-6"><input type="radio" class="btn-check" name="type" id="t_{{ $k }}" value="{{ $k }}" x-model="type" autocomplete="off">
                                <label class="btn btn-white w-100 h-100 text-start d-block p-3" for="t_{{ $k }}"><i class="bi bi-{{ $ic }} fs-5 d-block mb-1"></i><span class="fw-semibold d-block">{{ $l }}</span><span class="small text-muted fw-normal">{{ $h }}</span></label></div>
                        @endforeach
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-sm-5"><label class="form-label" for="branch_id">Branch</label><select id="branch_id" name="branch_id" x-model.number="branch" class="form-select" required>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                    <div class="col-sm-7"><label class="form-label" for="product_id">Product</label><select id="product_id" name="product_id" x-model.number="product" class="form-select" required><option value="">Select product…</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></div>
                    <div class="col-sm-5"><label class="form-label" for="quantity" x-text="type === 'adjustment' ? 'Counted quantity' : 'Quantity'"></label>
                        <div class="input-group"><input id="quantity" name="quantity" type="number" min="0" :step="products[product]?.decimal ? '0.001' : '1'" x-model="qty" class="form-control tabular" required><span class="input-group-text" x-text="unit || 'unit'"></span></div></div>
                    <div class="col-sm-7"><label class="form-label" for="reason">Reason <span class="text-muted fw-normal" x-show="type === 'stock_in' || type === 'opening'">(optional)</span></label>
                        <input id="reason" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="255" :required="type === 'stock_out' || type === 'adjustment'" list="reasons" placeholder="e.g. Supplier delivery #123">
                        <datalist id="reasons"><option>Supplier delivery</option><option>Damaged</option><option>Expired</option><option>Physical count correction</option><option>Transfer from another branch</option><option>Transfer to another branch</option></datalist></div>
                </div>
                <div class="sticky-actions"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Record movement</button></div>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card" x-show="product" x-cloak>
            <div class="card-header"><h2>Preview</h2></div>
            <div class="card-body">
                <div class="fw-semibold mb-3" x-text="products[product]?.name"></div>
                <div class="d-flex justify-content-between align-items-center text-center">
                    <div><div class="small-caps">Current</div><div class="fs-3 fw-bold tabular" x-text="fmt(current)"></div><div class="small text-muted" x-text="unit"></div></div>
                    <i class="bi bi-arrow-right fs-3 text-muted"></i>
                    <div><div class="small-caps">After</div><div class="fs-3 fw-bold tabular" :class="after < 0 ? 'text-danger' : 'text-success'" x-text="fmt(after)"></div><div class="small text-muted" x-text="unit"></div></div>
                </div>
                <div x-show="after < 0" class="alert alert-danger small mt-3 mb-0">Stock cannot go below zero.</div>
            </div>
        </div>
        <div class="card mt-3"><div class="card-body small text-muted">
            <p class="mb-2"><strong class="text-dark">Stock in / out</strong> add or remove the quantity entered.</p>
            <p class="mb-2"><strong class="text-dark">Count correction</strong> sets stock to the physically counted amount and records the difference.</p>
            <p class="mb-0"><strong class="text-dark">Opening stock</strong> can be entered once per product per branch.</p>
        </div></div>
    </div>
</div>
@endsection
