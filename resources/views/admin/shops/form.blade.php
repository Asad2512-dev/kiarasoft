@extends('layouts.app', ['title' => $shop->exists ? 'Edit shop' : 'Onboard shop'])
@section('content')
<div class="page-head"><div><a href="{{ $shop->exists ? route('admin.shops.show', $shop) : route('admin.shops.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Back</a><h1 class="mt-1">{{ $shop->exists ? 'Edit '.$shop->name : 'Onboard a new shop' }}</h1>
    @unless($shop->exists)<p>Creates the shop, default settings, a "Main Branch" and the Shop Admin account with a temporary password.</p>@endunless</div></div>
<form method="POST" action="{{ $shop->exists ? route('admin.shops.update', $shop) : route('admin.shops.store') }}" class="card" style="max-width: 820px" @if($shop->exists) data-confirm="Update the details of {{ $shop->name }}?" data-confirm-title="Update shop?" @endif>
    @csrf @if($shop->exists) @method('PUT') @endif
    <div class="card-body"><div class="row g-3">
        @php $n = $shop->exists ? 'name' : 'shop_name'; @endphp
        <div class="col-sm-7"><label class="form-label" for="{{ $n }}">Shop name</label><input id="{{ $n }}" name="{{ $n }}" value="{{ old($n, $shop->name) }}" class="form-control @error($n) is-invalid @enderror" required>@error($n)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-sm-5"><label class="form-label" for="owner_name">Owner name</label><input id="owner_name" name="owner_name" value="{{ old('owner_name', $shop->owner_name) }}" class="form-control @error('owner_name') is-invalid @enderror" required></div>
        <div class="col-sm-7"><label class="form-label" for="email">{{ $shop->exists ? 'Shop e-mail' : 'Owner e-mail (Shop Admin login)' }}</label><input id="email" type="email" name="email" value="{{ old('email', $shop->email) }}" class="form-control @error('email') is-invalid @enderror" {{ $shop->exists ? '' : 'required' }}>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-sm-5"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $shop->phone) }}" class="form-control"></div>
        <div class="col-sm-8"><label class="form-label" for="address">Address</label><input id="address" name="address" value="{{ old('address', $shop->address) }}" class="form-control"></div>
        <div class="col-sm-4"><label class="form-label" for="city">City</label><input id="city" name="city" value="{{ old('city', $shop->city) }}" class="form-control"></div>
    </div>
    <div class="sticky-actions"><a href="{{ route('admin.shops.index') }}" class="btn btn-white">Cancel</a><button class="btn btn-primary">{{ $shop->exists ? 'Save changes' : 'Create shop' }}</button></div></div>
</form>
@endsection
