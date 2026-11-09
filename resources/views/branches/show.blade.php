@extends('layouts.app', ['title' => $branch->name])
@section('content')
<div class="page-head">
    <div><a href="{{ route('branches.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Branches</a>
        <h1 class="mt-1">{{ $branch->name }} <span class="mono text-muted fs-6">{{ $branch->code }}</span> <x-active-badge :active="$branch->is_active"/></h1>
        <p><i class="bi bi-geo-alt"></i> {{ $branch->address ?: 'No address' }} @if($branch->phone)· <i class="bi bi-telephone"></i> {{ $branch->phone }}@endif · Manager: <strong>{{ $branch->manager?->name ?? 'Unassigned' }}</strong></p></div>
    <div class="actions">
        @can('reports.view')<a href="{{ route('reports.index', ['branch' => $branch->id]) }}" class="btn btn-white"><i class="bi bi-bar-chart-line"></i> Branch report</a>@endcan
        @can('update', $branch)
            <form method="POST" action="{{ route('branches.toggle', $branch) }}" data-confirm-title="Change status?" data-confirm-button="Yes, continue" data-confirm-tone="danger" data-confirm="{{ $branch->is_active ? 'Deactivate' : 'Activate' }} {{ $branch->name }}?">@csrf @method('PATCH')<button class="btn btn-white">{{ $branch->is_active ? 'Deactivate' : 'Activate' }}</button></form>
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Today's net sales" :value="\App\Support\Money::format($todaySummary['net'])" icon="cash-stack" :foot="$todaySummary['invoices'].' invoices'"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Month net sales" :value="\App\Support\Money::format($summary['net'])" icon="calendar3" tint="violet" :foot="$summary['invoices'].' invoices'"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Month returns" :value="\App\Support\Money::format($summary['returns'])" icon="arrow-counterclockwise" tint="amber"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Staff" :value="$staff->count()" icon="people" tint="blue"/></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h2>Sales — last 14 days</h2></div><div class="card-body"><div class="chart-wrap sm"><canvas role="img" aria-label="Branch sales chart" data-chart="{{ json_encode(["type" => "bar", "labels" => array_column($trend, "label"), "datasets" => [["label" => "Net sales", "data" => array_map("floatval", array_column($trend, "net"))]]]) }}"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h2>Staff</h2></div>
        <div class="list-group list-group-flush">@forelse($staff as $s)
            <div class="list-group-item d-flex align-items-center gap-2"><span class="avatar avatar-sm">{{ $s->initials() }}</span><div class="min-w-0 flex-grow-1"><div class="fw-550 small text-truncate">{{ $s->name }}</div><div class="small text-muted">{{ $s->role->label() }}</div></div><x-active-badge :active="$s->is_active"/></div>
        @empty<x-empty icon="people" title="No staff assigned"/>@endforelse</div></div></div>
</div>
<div class="row g-3">
    <div class="col-xl-7"><div class="card table-card">
        <div class="card-header"><h2>Branch inventory</h2><a href="{{ route('inventory.index', ['branch' => $branch->id]) }}" class="small">Full inventory</a></div>
        @if($inventory->isEmpty())<x-empty icon="boxes" title="No stock at this branch yet"/>@else
        <div class="table-responsive"><table class="table table-stack"><thead><tr><th>Product</th><th class="text-end">Qty</th><th>Status</th></tr></thead><tbody>
            @foreach($inventory as $i)<tr><td class="cell-primary"><span class="cell-title">{{ $i->product->name }}</span><div class="cell-sub">{{ $i->product->category?->name }}</div></td><td data-label="Qty" class="text-end tabular">@qty($i->quantity) {{ $i->product->unit->short() }}</td><td data-label="Status"><x-stock-badge :status="$i->stockStatus($default)"/></td></tr>@endforeach
        </tbody></table></div>
        <div class="card-footer">{{ $inventory->links() }}</div>@endif
    </div></div>
    <div class="col-xl-5"><div class="card table-card">
        <div class="card-header"><h2>Recent sales</h2><a href="{{ route('sales.index', ['branch' => $branch->id]) }}" class="small">All sales</a></div>
        @if($sales->isEmpty())<x-empty icon="receipt" title="No sales yet"/>@else
        <table class="table table-stack"><tbody>@foreach($sales as $s)<tr><td class="cell-primary"><a class="mono cell-title" href="{{ route('sales.show', $s) }}">{{ $s->invoice_no }}</a><div class="cell-sub">{{ $s->cashier->name }} · {{ $s->sold_at->diffForHumans() }}</div></td><td data-label="Total" class="text-end tabular fw-semibold">@money($s->total)</td></tr>@endforeach</tbody></table>@endif
    </div></div>
</div>
@endsection
