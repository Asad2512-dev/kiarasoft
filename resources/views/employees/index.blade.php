@extends('layouts.app', ['title' => 'Employees'])
@section('content')
<div class="page-head">
    <div><h1>Employees</h1><p>Manage staff accounts, roles and branch assignments.</p></div>
    <div class="actions">@can('employees.manage')<a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add employee</a>@endcan</div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, e-mail or phone" aria-label="Search employees"></div>
            <select name="role" class="form-select" data-autosubmit aria-label="Role"><option value="">All roles</option>@foreach(['shop_admin' => 'Shop Admin', 'branch_manager' => 'Branch Manager', 'cashier' => 'Cashier'] as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach</select>
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <select name="active" class="form-select" data-autosubmit aria-label="Status"><option value="">Any status</option><option value="1" @selected(request('active') === '1')>Active</option><option value="0" @selected(request('active') === '0')>Inactive</option></select>
            <button class="btn btn-white" data-no-lock>Search</button>
        </form>
    </div>
    @if($employees->isEmpty())
        <x-empty icon="person-badge" title="No employees found"/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Employee</th><th>Role</th><th>Branches</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($employees as $e)
            <tr>
                <td class="cell-primary"><div class="d-flex align-items-center gap-2"><span class="avatar avatar-sm">{{ $e->initials() }}</span><div class="min-w-0"><div class="cell-title">{{ $e->name }}</div><div class="cell-sub text-truncate">{{ $e->email }}{{ $e->phone ? ' · '.$e->phone : '' }}</div></div></div></td>
                <td data-label="Role"><span class="badge-soft badge-soft-{{ ['shop_admin' => 'primary', 'branch_manager' => 'info', 'cashier' => 'secondary'][$e->role->value] ?? 'secondary' }}">{{ $e->role->label() }}</span></td>
                <td data-label="Branches" class="small">{{ $e->isShopAdmin() ? 'All branches' : ($e->branches->pluck('name')->implode(', ') ?: '—') }}</td>
                <td data-label="Last sign-in" class="small text-muted">{{ $e->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                <td data-label="Status"><x-active-badge :active="$e->is_active"/></td>
                <td class="cell-actions text-end text-nowrap">
                    @can('update', $e)
                        <a href="{{ route('employees.edit', $e) }}" class="btn btn-sm btn-white"><i class="bi bi-pencil"></i> Edit</a>
                        <div class="dropdown d-inline">
                            <button class="btn btn-sm btn-white btn-icon" data-bs-toggle="dropdown" aria-label="More actions for {{ $e->name }}"><i class="bi bi-three-dots"></i></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <form method="POST" action="{{ route('employees.password', $e) }}" data-confirm="Generate a new temporary password for {{ $e->name }}? They will be signed out.">@csrf<button class="dropdown-item"><i class="bi bi-key me-2"></i>Reset password</button></form>
                                <form method="POST" action="{{ route('employees.toggle', $e) }}" data-confirm="{{ $e->is_active ? 'Deactivate' : 'Activate' }} {{ $e->name }}?">@csrf @method('PATCH')<button class="dropdown-item {{ $e->is_active ? 'text-danger' : '' }}"><i class="bi bi-{{ $e->is_active ? 'person-x' : 'person-check' }} me-2"></i>{{ $e->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            </div>
                        </div>
                    @endcan
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $employees->links() }}</div>
    @endif
</div>
@endsection
