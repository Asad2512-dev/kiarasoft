@extends('layouts.app', ['title' => $product->exists ? 'Edit product' : 'Add product'])
@section('content')
<div class="page-head">
    <div><a href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Back</a>
        <h1 class="mt-1">{{ $product->exists ? 'Edit '.$product->name : 'Add a product' }}</h1></div>
</div>
@if($categories->isEmpty())
    <div class="alert alert-warning">You need at least one active category first. <a href="{{ route('categories.index') }}">Create a category</a>.</div>
@endif
<form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" class="card" x-data="{ unit: @js(old('unit', $product->unit?->value)) }">
    @csrf @if($product->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Basic information</div><div class="section-help">How the product appears at the POS and on receipts.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-12"><label class="form-label" for="name">Product name</label><input id="name" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="150" placeholder="e.g. Basmati Rice Super Kernel">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6"><label class="form-label" for="sku">SKU</label><input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control mono text-uppercase @error('sku') is-invalid @enderror" required maxlength="50" placeholder="RICE-BAS-5KG">@error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6"><label class="form-label" for="barcode">Barcode <span class="text-muted fw-normal">(optional)</span></label><input id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-control mono" maxlength="60" inputmode="numeric"></div>
                <div class="col-sm-6"><label class="form-label" for="category_id">Category</label><select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required><option value="">Select…</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select>@error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6"><label class="form-label" for="unit">Unit of measurement</label><select id="unit" name="unit" x-model="unit" class="form-select @error('unit') is-invalid @enderror" required>@foreach($units as $u)<option value="{{ $u->value }}">{{ $u->label() }}</option>@endforeach</select>
                    <div class="form-text" x-text="['kg','g','l','ml'].includes(unit) ? 'Decimal quantities allowed (e.g. 1.25)' : 'Sold in whole units only'"></div></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Pricing</div><div class="section-help">Selling price is charged at the POS. Editing it never changes past invoices.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="price">Selling price</label><div class="input-group"><span class="input-group-text">Rs</span><input id="price" name="price" type="number" step="0.01" min="0.01" value="{{ old('price', $product->price) }}" class="form-control tabular @error('price') is-invalid @enderror" required>@error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                <div class="col-sm-6"><label class="form-label" for="cost_price">Cost price <span class="text-muted fw-normal">(optional)</span></label><div class="input-group"><span class="input-group-text">Rs</span><input id="cost_price" name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price', $product->cost_price) }}" class="form-control tabular @error('cost_price') is-invalid @enderror">@error('cost_price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Stock settings</div><div class="section-help">Leave the threshold blank to use the shop default.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="low_stock_threshold">Low-stock threshold</label><input id="low_stock_threshold" name="low_stock_threshold" type="number" step="0.001" min="0" value="{{ old('low_stock_threshold', $product->low_stock_threshold !== null ? (float) $product->low_stock_threshold : '') }}" class="form-control tabular" placeholder="Shop default"></div>
                <div class="col-sm-6 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))><label class="form-check-label" for="is_active">Active (available at POS)</label></div></div>
                @unless($product->exists)
                    @if($branches->isNotEmpty())
                    <div class="col-12"><label class="form-label">Opening stock <span class="text-muted fw-normal">(optional, per branch)</span></label>
                        <div class="row g-2">@foreach($branches as $b)<div class="col-sm-6 col-md-4"><div class="input-group input-group-sm"><span class="input-group-text text-truncate" style="max-width: 60%">{{ $b->name }}</span><input type="number" name="opening[{{ $b->id }}]" value="{{ old('opening.'.$b->id) }}" min="0" step="0.001" class="form-control tabular" aria-label="Opening stock at {{ $b->name }}" placeholder="0"></div></div>@endforeach</div>
                        <div class="form-text">Recorded as an "Opening stock" movement for each branch.</div></div>
                    @endif
                @endunless
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Image</div><div class="section-help">Optional. JPG, PNG or WebP up to 2&nbsp;MB.</div></div>
            <div class="col-lg-8">
                @if($product->image_path)<div class="d-flex align-items-center gap-3 mb-2"><img src="{{ $product->imageUrl() }}" alt="" class="product-thumb" style="width:64px;height:64px"><div class="form-check"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label" for="remove_image">Remove image</label></div></div>@endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" aria-label="Product image">@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="sticky-actions">
            <a href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}" class="btn btn-white">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $product->exists ? 'Save changes' : 'Create product' }}</button>
        </div>
    </div>
</form>
@endsection
