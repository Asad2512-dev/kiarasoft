@extends('layouts.app', ['title' => $shop->name])
@section('content')
<div class="page-head">
    <div><a href="{{ route('admin.shops.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Shops</a><h1 class="mt-1">{{ $shop->name }} <x-active-badge :active="$shop->isActive()" off="Suspended"/></h1><p>{{ $shop->owner_name }} · {{ $shop->city ?: 'No city' }} · joined {{ $shop->created_at->format('d M Y') }}</p></div>
    <div class="actions">
        <form method="POST" action="{{ route('admin.shops.toggle', $shop) }}" data-confirm-title="Change status?" data-confirm-button="Yes, continue" data-confirm-tone="danger" data-confirm="{{ $shop->isActive() ? 'Suspend' : 'Activate' }} {{ $shop->name }}?">@csrf @method('PATCH')<button class="btn {{ $shop->isActive() ? 'btn-soft-danger' : 'btn-soft-primary' }}">{{ $shop->isActive() ? 'Suspend shop' : 'Activate shop' }}</button></form>
        <a href="{{ route('admin.shops.edit', $shop) }}" class="btn btn-white"><i class="bi bi-pencil"></i> Edit</a>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Branches" :value="$shop->branches_count" icon="diagram-3"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Users" :value="$shop->users_count" icon="people" tint="blue"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Products" :value="$shop->products_count" icon="box-seam" tint="violet"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Lifetime invoices" :value="number_format($salesCount)" icon="receipt" tint="amber" :foot="\App\Support\Money::format($salesTotal)"/></div>
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card table-card"><div class="card-header"><h2>Branches</h2></div>
        <table class="table table-stack"><thead><tr><th>Branch</th><th>Manager</th><th>Status</th></tr></thead><tbody>
        @foreach($branches as $b)<tr><td class="cell-primary"><span class="cell-title">{{ $b->name }}</span> <span class="mono small text-muted">{{ $b->code }}</span></td><td data-label="Manager">{{ $b->manager?->name ?? '—' }}</td><td data-label="Status"><x-active-badge :active="$b->is_active"/></td></tr>@endforeach
        </tbody></table></div></div>
    <div class="col-lg-6"><div class="card table-card"><div class="card-header"><h2>Users</h2><span class="small text-muted">Passwords are never visible</span></div>
        <table class="table table-stack"><thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead><tbody>
        @foreach($users as $u)<tr><td class="cell-primary"><span class="cell-title">{{ $u->name }}</span><div class="cell-sub">{{ $u->email }}</div></td><td data-label="Role">{{ $u->role->label() }}</td><td data-label="Status"><x-active-badge :active="$u->is_active"/></td></tr>@endforeach
        </tbody></table></div></div>
</div>
@endsection
