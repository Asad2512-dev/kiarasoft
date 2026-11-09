@extends('layouts.app', ['title' => 'Platform overview'])
@section('content')
<div class="page-head"><div><h1>Platform overview</h1><p>All KiraSoft shops at a glance.</p></div><div class="actions"><a href="{{ route('admin.shops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Onboard shop</a></div></div>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><x-stat label="Shops" :value="$stats['shops']" icon="shop" :foot="$stats['active_shops'].' active · '.($stats['shops'] - $stats['active_shops']).' suspended'" :href="route('admin.shops.index')"/></div>
    <div class="col-6 col-xl-3"><x-stat label="Branches" :value="$stats['branches']" icon="diagram-3" tint="blue"/></div>
    <div class="col-6 col-xl-3"><x-stat label="Shop users" :value="$stats['users']" icon="people" tint="violet" :href="route('admin.users.index')"/></div>
    <div class="col-6 col-xl-3"><x-stat label="Pending signups" :value="$stats['pending']" icon="person-plus" tint="amber" :foot="$stats['sales_today'].' invoices platform-wide today'" :href="route('admin.signups.index')"/></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h2>New shops</h2><span class="small text-muted">Last 6 months</span></div><div class="card-body"><div class="chart-wrap sm"><canvas role="img" aria-label="New shops chart" data-chart="{{ json_encode(["type" => "bar", "money" => false, "labels" => $chart["labels"], "datasets" => [["label" => "Shops", "data" => $chart["data"]]]]) }}"></canvas></div></div></div></div>
    <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h2>Pending signup requests</h2><a href="{{ route('admin.signups.index') }}" class="small">Review</a></div>
        <div class="list-group list-group-flush">@forelse($signups as $s)
            <div class="list-group-item"><div class="fw-550">{{ $s->shop_name }}</div><div class="small text-muted">{{ $s->owner_name }} · {{ $s->city ?: '—' }} · {{ $s->created_at->diffForHumans() }}</div></div>
        @empty<x-empty icon="inbox" title="No pending requests"/>@endforelse</div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7"><div class="card table-card"><div class="card-header"><h2>Recent shops</h2><a href="{{ route('admin.shops.index') }}" class="small">All shops</a></div>
        <div class="table-responsive"><table class="table table-hover table-stack"><thead><tr><th>Shop</th><th>City</th><th class="text-center">Branches</th><th class="text-center">Users</th><th>Status</th></tr></thead><tbody>
        @foreach($shops as $s)<tr><td class="cell-primary"><a href="{{ route('admin.shops.show', $s) }}" class="cell-title">{{ $s->name }}</a><div class="cell-sub">{{ $s->owner_name }}</div></td><td data-label="City">{{ $s->city }}</td><td data-label="Branches" class="text-md-center">{{ $s->branches_count }}</td><td data-label="Users" class="text-md-center">{{ $s->users_count }}</td><td data-label="Status"><x-active-badge :active="$s->isActive()" off="Suspended"/></td></tr>@endforeach
        </tbody></table></div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header"><h2>Platform activity</h2><a href="{{ route('admin.activity') }}" class="small">All</a></div><div class="card-body pt-0">@include('activity._timeline', ['logs' => $logs])</div></div></div>
</div>
@endsection
