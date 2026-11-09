@extends('layouts.app', ['title' => 'Shops'])
@section('content')
<div class="page-head"><div><h1>Shops</h1><p>{{ $shops->total() }} shop account(s).</p></div><div class="actions"><a href="{{ route('admin.shops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Onboard shop</a></div></div>
<div class="card table-card">
    <div class="card-header"><form class="filter-bar w-100" method="GET">
        <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Shop, owner or city" aria-label="Search shops"></div>
        <select name="status" class="form-select" data-autosubmit aria-label="Status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="suspended" @selected(request('status') === 'suspended')>Suspended</option></select>
        <select name="sort" class="form-select" data-autosubmit aria-label="Sort"><option value="created_at">Newest first</option><option value="name" @selected(request('sort') === 'name')>Name</option><option value="city" @selected(request('sort') === 'city')>City</option></select>
        <button class="btn btn-white" data-no-lock>Search</button>
    </form></div>
    @if($shops->isEmpty())<x-empty icon="shop" title="No shops found"/>@else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Shop</th><th>Contact</th><th>City</th><th class="text-center">Branches</th><th class="text-center">Users</th><th>Joined</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($shops as $s)
            <tr>
                <td class="cell-primary"><a href="{{ route('admin.shops.show', $s) }}" class="cell-title">{{ $s->name }}</a><div class="cell-sub">{{ $s->owner_name }}</div></td>
                <td data-label="Contact" class="small">{{ $s->email }}<br class="d-none d-md-inline"> {{ $s->phone }}</td>
                <td data-label="City">{{ $s->city ?: '—' }}</td>
                <td data-label="Branches" class="text-md-center">{{ $s->branches_count }}</td>
                <td data-label="Users" class="text-md-center">{{ $s->users_count }}</td>
                <td data-label="Joined" class="small text-muted">{{ $s->created_at->format('d M Y') }}</td>
                <td data-label="Status"><x-active-badge :active="$s->isActive()" off="Suspended"/></td>
                <td class="cell-actions text-end text-nowrap">
                    <form method="POST" action="{{ route('admin.shops.toggle', $s) }}" class="d-inline" data-confirm-title="Change status?" data-confirm-button="Yes, continue" data-confirm-tone="danger" data-confirm="{{ $s->isActive() ? 'Suspend' : 'Activate' }} {{ $s->name }}? {{ $s->isActive() ? 'All its users will be signed out.' : '' }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $s->isActive() ? 'btn-soft-danger' : 'btn-soft-primary' }}">{{ $s->isActive() ? 'Suspend' : 'Activate' }}</button></form>
                    <a href="{{ route('admin.shops.show', $s) }}" class="btn btn-sm btn-white">View</a>
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $shops->links() }}</div>@endif
</div>
@endsection
