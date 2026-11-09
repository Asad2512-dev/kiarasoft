@extends('layouts.app', ['title' => 'Dashboard'])
@section('content')
@php
    use App\Support\Money;
    $t = $stats['today']; $y = $stats['yesterday'];
    $tm = Money::toMinor($t['net']); $ym = Money::toMinor($y['net']);
    $delta = $ym > 0 ? round(($tm - $ym) * 100 / $ym, 1) : null;
@endphp
<div class="page-head">
    <div>
        <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ strtok(auth()->user()->name, ' ') }}</h1>
        <p>Here's how {{ auth()->user()->shop->name }} is doing today, {{ now()->format('l, j F Y') }}.</p>
    </div>
    <form class="actions" method="GET">
        @if($branches->count() > 1)
            <select name="branch" class="form-select" data-autosubmit aria-label="Branch">
                <option value="">All branches</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        @endif
        @can('reports.view')<a href="{{ route('reports.index', request()->only('branch')) }}" class="btn btn-white text-nowrap"><i class="bi bi-bar-chart-line"></i> Reports</a>@endcan
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <x-stat label="Today's net sales" :value="Money::format($t['net'])" icon="cash-stack" tint="brand">
            @if($delta !== null)<span class="{{ $delta >= 0 ? 'trend-up' : 'trend-down' }} fw-semibold"><i class="bi bi-arrow-{{ $delta >= 0 ? 'up' : 'down' }}-right"></i> {{ abs($delta) }}%</span> vs yesterday
            @else Yesterday: {{ Money::format($y['net']) }} @endif
        </x-stat>
    </div>
    <div class="col-6 col-xl-3"><x-stat label="Invoices today" :value="number_format($t['invoices'])" icon="receipt" tint="blue" :foot="'Avg. '.Money::format($t['average'])" :href="route('sales.index', ['period' => 'today'])"/></div>
    <div class="col-6 col-xl-3"><x-stat label="Month to date" :value="Money::format($stats['month']['net'])" icon="calendar3" tint="violet" :foot="number_format($stats['month']['invoices']).' invoices this month'"/></div>
    <div class="col-6 col-xl-3"><x-stat label="Stock alerts" :value="number_format($stats['alerts']['total'])" icon="exclamation-triangle" tint="{{ $stats['alerts']['out'] ? 'red' : 'amber' }}" :foot="$stats['alerts']['out'].' out · '.$stats['alerts']['low'].' low'" :href="route('alerts.index')"/></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><div><h2>Sales trend</h2><div class="small text-muted">Last 14 days · net of returns</div></div>
                <div class="d-flex gap-3 small"><span><span class="text-muted">Products</span> <strong>{{ $stats['products'] }}</strong></span><span><span class="text-muted">Active branches</span> <strong>{{ $stats['active_branches'] }}</strong></span></div></div>
            <div class="card-body"><div class="chart-wrap"><canvas aria-label="Sales trend chart" role="img" data-chart="{{ json_encode(["type" => "line", "labels" => array_column($trend, "label"), "datasets" => [["label" => "Net sales", "data" => array_map("floatval", array_column($trend, "net"))], ["label" => "Invoices", "data" => array_column($trend, "invoices"), "hidden" => true, "color" => "#2e90fa"]]]) }}"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><div><h2>Branch comparison</h2><div class="small text-muted">This month</div></div></div>
            <div class="card-body">
                @php $maxB = max(1, ...array_map(fn($b) => Money::toMinor($b['net']), $branchComparison ?: [['net' => 0]])); @endphp
                @forelse($branchComparison as $b)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1"><span class="fw-550">{{ $b['name'] }}</span><span class="tabular fw-semibold">{{ Money::format($b['net']) }}</span></div>
                        <div class="progress progress-thin"><div class="progress-bar bg-primary" style="width: {{ max(2, Money::toMinor($b['net']) * 100 / $maxB) }}%"></div></div>
                        <div class="small text-muted mt-1">{{ $b['invoices'] }} invoices</div>
                    </div>
                @empty
                    <x-empty icon="diagram-3" title="No branches"/>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card table-card mb-3">
            <div class="card-header"><h2>Recent sales</h2><a href="{{ route('sales.index') }}" class="btn btn-sm btn-white">View all</a></div>
            @if($recentSales->isEmpty())
                <x-empty icon="receipt" title="No sales yet" text="Sales made at the POS will appear here.">@can('pos.use')<a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-cart3"></i> Open POS</a>@endcan</x-empty>
            @else
            <div class="table-responsive"><table class="table table-hover table-stack">
                <thead><tr><th>Invoice</th><th>Branch</th><th>Cashier</th><th>Time</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach($recentSales as $s)
                    <tr>
                        <td class="cell-primary"><a href="{{ route('sales.show', $s) }}" class="cell-title mono">{{ $s->invoice_no }}</a> @if($s->status->value !== 'completed')<span class="badge-soft badge-soft-{{ $s->status->badge() }} ms-1">{{ $s->status->label() }}</span>@endif</td>
                        <td data-label="Branch">{{ $s->branch->name }}</td>
                        <td data-label="Cashier">{{ $s->cashier->name }}</td>
                        <td data-label="Time" class="text-muted">{{ $s->sold_at->diffForHumans() }}</td>
                        <td data-label="Total" class="text-end fw-semibold tabular">@money($s->total)</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>
        <div class="card">
            <div class="card-header"><div><h2>Top products</h2><div class="small text-muted">Last 30 days by revenue</div></div></div>
            <div class="card-body">
                @if(empty($topProducts))<x-empty icon="trophy" title="No product sales yet"/>@else
                <div class="chart-wrap sm"><canvas role="img" aria-label="Top products chart" data-chart="{{ json_encode(["type" => "bar", "horizontal" => true, "labels" => array_column($topProducts, "product_name"), "datasets" => [["label" => "Revenue", "data" => array_map("floatval", array_column($topProducts, "revenue"))]]]) }}"></canvas></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h2>Low stock</h2><a href="{{ route('alerts.index') }}" class="small">All alerts</a></div>
            <div class="list-group list-group-flush">
                @forelse($lowStock as $i)
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0"><div class="fw-550 text-truncate">{{ $i->product_name }}</div><div class="small text-muted">{{ $i->branch_name }} · threshold @qty($i->effective_threshold)</div></div>
                        <span class="badge-soft badge-soft-{{ (float) $i->quantity <= 0 ? 'danger' : 'warning' }} text-nowrap">@qty($i->quantity) {{ \App\Enums\Unit::from($i->unit)->short() }}</span>
                    </div>
                @empty
                    <x-empty icon="check2-circle" title="All stocked up" text="No products are at or below their threshold."/>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2>Recent activity</h2>@can('logs.view')<a href="{{ route('activity.index') }}" class="small">View log</a>@endcan</div>
            <div class="card-body pt-0">@include('activity._timeline', ['logs' => $activity])</div>
        </div>
    </div>
</div>
@endsection
