@extends('layouts.app', ['title' => 'Activity log'])
@section('content')
<div class="page-head"><div><h1>{{ $platform ? 'Platform activity' : 'Activity log' }}</h1><p>Audit trail of sign-ins, sales, stock changes and administrative actions.</p></div></div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search descriptions" aria-label="Search"></div>
            <select name="action" class="form-select" data-autosubmit aria-label="Action type"><option value="">All actions</option>
                @foreach(['auth' => 'Sign-in / out', 'sale' => 'Sales & returns', 'inventory' => 'Inventory', 'product' => 'Products', 'category' => 'Categories', 'employee' => 'Employees', 'branch' => 'Branches', 'settings' => 'Settings', 'shop' => 'Shops', 'system' => 'System', 'report' => 'Reports'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>@endforeach</select>
            @if($platform)<select name="shop" class="form-select" data-autosubmit aria-label="Shop"><option value="">All shops</option>@foreach($shops as $s)<option value="{{ $s->id }}" @selected(request('shop') == $s->id)>{{ $s->name }}</option>@endforeach</select>@endif
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <x-date-filter :from="$from" :to="$to"/>
            <button class="btn btn-white" data-no-lock>Filter</button>
        </form>
    </div>
    @if($logs->isEmpty())
        <x-empty icon="clock-history" title="No activity in this period"/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th>@if($platform)<th>Shop</th>@endif<th>Branch</th><th>IP</th></tr></thead>
        <tbody>@foreach($logs as $l)
            <tr>
                <td data-label="When" class="text-nowrap small">{{ $l->created_at->format('d M Y, h:i:s A') }}</td>
                <td data-label="User">{{ $l->user?->name ?? 'System' }}</td>
                <td data-label="Action"><span class="badge-soft badge-soft-{{ str_starts_with($l->action, 'auth.failed') ? 'danger' : 'secondary' }} mono">{{ $l->action }}</span></td>
                <td class="cell-primary" style="max-width: 420px">{{ $l->description }}</td>
                @if($platform)<td data-label="Shop">{{ $l->shop?->name ?? '—' }}</td>@endif
                <td data-label="Branch">{{ $l->branch?->name ?? '—' }}</td>
                <td data-label="IP" class="small text-muted mono">{{ $l->ip_address ?? '—' }}</td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
