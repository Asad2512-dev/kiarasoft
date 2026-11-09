@extends('layouts.app', ['title' => $product->name])
@section('content')
<div class="page-head">
    <div class="d-flex gap-3 align-items-center">
        @if($product->image_path)<img src="{{ $product->imageUrl() }}" alt="" class="product-thumb" style="width:60px;height:60px">@else<span class="product-thumb" style="width:60px;height:60px;font-size:1.5rem"><i class="bi bi-box"></i></span>@endif
        <div><a href="{{ route('products.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Products</a><h1 class="mt-1">{{ $product->name }}</h1><p class="mono">{{ $product->sku }} · {{ $product->category?->name }}</p></div>
    </div>
    <div class="actions">
        @can('inventory.adjust')<a href="{{ route('inventory.create', ['product' => $product->id]) }}" class="btn btn-white"><i class="bi bi-plus-slash-minus"></i> Adjust stock</a>@endcan
        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <form method="POST" action="{{ route('products.archive', $product) }}" data-confirm="Archive {{ $product->name }}? It will be hidden from the POS but invoices are kept.">@csrf @method('DELETE')<button class="btn btn-soft-danger"><i class="bi bi-archive"></i> Archive</button></form>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Selling price" :value="\App\Support\Money::format($product->price)" icon="tag" :foot="'per '.$product->unit->short()"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Total stock" :value="\App\Support\Money::qty($stock->sum(fn($s) => (float)$s->quantity)).' '.$product->unit->short()" icon="boxes" tint="blue" :foot="$stock->count().' branch(es)'"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Sold (30 days)" :value="\App\Support\Money::qty($sold->qty).' '.$product->unit->short()" icon="cart-check" tint="violet"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Revenue (30 days)" :value="\App\Support\Money::format($sold->revenue)" icon="cash-stack" tint="amber" :foot="$product->cost_price ? 'Cost '.\App\Support\Money::format($product->cost_price) : null"/></div>
</div>
<div class="row g-3">
    <div class="col-lg-5"><div class="card table-card h-100">
        <div class="card-header"><h2>Stock by branch</h2></div>
        @if($stock->isEmpty())<x-empty icon="boxes" title="No stock recorded" text="Record opening stock to make this product sellable."/>@else
        <table class="table table-stack"><thead><tr><th>Branch</th><th class="text-end">Quantity</th><th>Status</th></tr></thead><tbody>
            @foreach($stock as $s)<tr><td class="cell-primary cell-title">{{ $s->branch->name }}</td><td data-label="Qty" class="text-end tabular">@qty($s->quantity) {{ $product->unit->short() }}</td><td data-label="Status"><x-stock-badge :status="$s->stockStatus($default)"/></td></tr>@endforeach
        </tbody></table>@endif
    </div></div>
    <div class="col-lg-7"><div class="card table-card h-100">
        <div class="card-header"><h2>Recent stock movements</h2><a href="{{ route('inventory.movements', ['product' => $product->id]) }}" class="small">Full history</a></div>
        @include('inventory._movements_table', ['movements' => $movements, 'showProduct' => false])
    </div></div>
</div>
@endsection
