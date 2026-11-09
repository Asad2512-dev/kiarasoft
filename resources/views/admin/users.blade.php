@extends('layouts.app', ['title' => 'Users'])
@section('content')
<div class="page-head"><div><h1>Platform users</h1><p>Read-only overview of all accounts.</p></div></div>
<div class="card table-card">
    <div class="card-header"><form class="filter-bar w-100" method="GET">
        <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or e-mail" aria-label="Search users"></div>
        <select name="role" class="form-select" data-autosubmit aria-label="Role"><option value="">All roles</option>@foreach(\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>@endforeach</select>
        <select name="active" class="form-select" data-autosubmit aria-label="Status"><option value="">Any status</option><option value="1" @selected(request('active') === '1')>Active</option><option value="0" @selected(request('active') === '0')>Inactive</option></select>
        <button class="btn btn-white" data-no-lock>Search</button>
    </form></div>
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>User</th><th>Role</th><th>Shop</th><th>Last sign-in</th><th>Status</th></tr></thead>
        <tbody>@foreach($users as $u)
            <tr><td class="cell-primary"><div class="d-flex gap-2 align-items-center"><span class="avatar avatar-sm">{{ $u->initials() }}</span><div><div class="cell-title">{{ $u->name }}</div><div class="cell-sub">{{ $u->email }}</div></div></div></td>
                <td data-label="Role">{{ $u->role->label() }}</td><td data-label="Shop">@if($u->shop)<a href="{{ route('admin.shops.show', $u->shop) }}">{{ $u->shop->name }}</a>@else — @endif</td>
                <td data-label="Last sign-in" class="small text-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td><td data-label="Status"><x-active-badge :active="$u->is_active"/></td></tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
