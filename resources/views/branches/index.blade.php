@extends('layouts.app', ['title' => 'Branches'])
@section('content')
<div class="page-head">
    <div><h1>Branches</h1><p>{{ $branches->count() }} branch(es){{ auth()->user()->isShopAdmin() ? '' : ' assigned to you' }}.</p></div>
    <div class="actions">@can('branches.manage')<a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add branch</a>@endcan</div>
</div>
<form class="filter-bar mb-3" method="GET">
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search branches" aria-label="Search branches"></div>
    <select name="active" class="form-select" data-autosubmit aria-label="Status"><option value="">All statuses</option><option value="1" @selected(request('active') === '1')>Active</option><option value="0" @selected(request('active') === '0')>Inactive</option></select>
</form>
@if($branches->isEmpty())
    <div class="card"><x-empty icon="diagram-3" title="No branches found"/></div>
@else
<div class="row g-3">
    @foreach($branches as $b)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100 {{ $b->is_active ? '' : 'opacity-75' }}">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex gap-3 align-items-center min-w-0">
                        <span class="stat-icon tint-brand rounded-3 d-grid flex-shrink-0" style="width:46px;height:46px;place-items:center"><i class="bi bi-shop"></i></span>
                        <div class="min-w-0"><a href="{{ route('branches.show', $b) }}" class="cell-title stretched-link text-truncate d-block">{{ $b->name }}</a><div class="cell-sub mono">{{ $b->code }}</div></div>
                    </div>
                    <x-active-badge :active="$b->is_active"/>
                </div>
                <div class="small text-muted mb-3"><i class="bi bi-geo-alt me-1"></i>{{ $b->address ?: 'No address' }}</div>
                <div class="row g-2 mt-auto text-center">
                    <div class="col-4"><div class="small text-muted">Today</div><div class="fw-semibold tabular small">@money($today[$b->id]->t ?? 0)</div></div>
                    <div class="col-4"><div class="small text-muted">Invoices</div><div class="fw-semibold">{{ $today[$b->id]->c ?? 0 }}</div></div>
                    <div class="col-4"><div class="small text-muted">Staff</div><div class="fw-semibold">{{ $b->users_count }}</div></div>
                </div>
                <div class="border-top mt-3 pt-3 small d-flex justify-content-between"><span class="text-muted">Manager</span><span class="fw-550">{{ $b->manager?->name ?? 'Unassigned' }}</span></div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
