@extends('layouts.app', ['title' => 'Stock alerts'])
@section('content')
<div class="page-head"><div><h1>Notification center</h1><p>Live low-stock and out-of-stock alerts for your branches. Alerts are shown in-app only.</p></div>
    <div class="actions">@can('inventory.adjust')<a href="{{ route('inventory.create') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> Receive stock</a>@endcan</div></div>
<div class="row g-3 mb-3">
    <div class="col-4"><x-stat label="All alerts" :value="$counts['total']" icon="bell" tint="gray" :href="route('alerts.index')"/></div>
    <div class="col-4"><x-stat label="Out of stock" :value="$counts['out']" icon="x-octagon" tint="red" :href="route('alerts.index', ['status' => 'out'])"/></div>
    <div class="col-4"><x-stat label="Low stock" :value="$counts['low']" icon="exclamation-triangle" tint="amber" :href="route('alerts.index', ['status' => 'low'])"/></div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search product" aria-label="Search"></div>
            <select name="status" class="form-select" data-autosubmit aria-label="Alert type"><option value="">All alerts</option><option value="out" @selected(request('status') === 'out')>Out of stock</option><option value="low" @selected(request('status') === 'low')>Low stock</option></select>
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <button class="btn btn-white" data-no-lock>Filter</button>
        </form>
    </div>
    @if($items->isEmpty())
        <x-empty icon="check2-circle" title="No alerts" text="Everything is above its low-stock threshold."/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Product</th><th>Branch</th><th class="text-end">In stock</th><th class="text-end">Threshold</th><th>Alert</th><th></th></tr></thead>
        <tbody>@foreach($items as $i)
            @php $out = (float)$i->quantity <= 0; @endphp
            <tr>
                <td class="cell-primary"><span class="cell-title">{{ $i->product_name }}</span><div class="cell-sub mono">{{ $i->sku }}</div></td>
                <td data-label="Branch">{{ $i->branch_name }}</td>
                <td data-label="In stock" class="text-end tabular fw-semibold {{ $out ? 'text-danger' : 'text-warning' }}">@qty($i->quantity) {{ \App\Enums\Unit::from($i->unit)->short() }}</td>
                <td data-label="Threshold" class="text-end tabular text-muted">@qty($i->effective_threshold)</td>
                <td data-label="Alert"><x-stock-badge :status="$out ? 'out' : 'low'"/></td>
                <td class="cell-actions text-end">@can('inventory.adjust')<a href="{{ route('inventory.create', ['product' => $i->product_id, 'branch' => $i->branch_id]) }}" class="btn btn-sm btn-soft-primary">Restock</a>@endcan</td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $items->links() }}</div>
    @endif
</div>
@endsection
