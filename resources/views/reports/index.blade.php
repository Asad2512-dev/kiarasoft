@extends('layouts.app', ['title' => 'Sales reports'])
@section('content')
@php use App\Support\Money; $q = request()->only(['branch','cashier','from','to','period']); @endphp
<div class="page-head">
    <div><h1>Sales reports</h1><p>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}{{ request('branch') ? ' · '.$branches->firstWhere('id', request('branch'))?->name : ' · all branches' }}{{ request('cashier') ? ' · '.$cashiers->firstWhere('id', request('cashier'))?->name : '' }}</p></div>
    <div class="actions">
        <button onclick="window.print()" class="btn btn-white"><i class="bi bi-printer"></i> Print</button>
        @can('reports.export')
        <div class="dropdown"><button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> Export CSV</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach(['daily' => 'Daily summary', 'branch' => 'By branch', 'cashier' => 'By cashier', 'products' => 'Product sales'] as $k => $v)
                    <li><a class="dropdown-item" href="{{ route('reports.export', $q + ['type' => $k]) }}">{{ $v }}</a></li>
                @endforeach
            </ul></div>
        @endcan
    </div>
</div>
<form class="card mb-3" method="GET"><div class="card-body filter-bar">
    <x-date-filter :from="$from" :to="$to"/>
    @if($branches->count() > 1)<select name="branch" class="form-select" aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
    <select name="cashier" class="form-select" aria-label="Cashier"><option value="">All cashiers</option>@foreach($cashiers as $c)<option value="{{ $c->id }}" @selected(request('cashier') == $c->id)>{{ $c->name }}</option>@endforeach</select>
    <button class="btn btn-primary" data-no-lock><i class="bi bi-arrow-repeat"></i> Run report</button>
</div></form>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Gross sales" :value="Money::format($summary['gross'])" icon="bag" tint="blue" foot="Before discounts & tax"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Discounts" :value="Money::format($summary['discounts'])" icon="percent" tint="violet" :foot="'Tax collected '.Money::format($summary['tax'])"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Returns" :value="Money::format($summary['returns'])" icon="arrow-counterclockwise" tint="amber" foot="Refunds processed in period"/></div>
    <div class="col-6 col-lg-3"><x-stat label="Net sales" :value="Money::format($summary['net'])" icon="cash-stack" tint="brand" :foot="$summary['invoices'].' invoices · avg '.Money::format($summary['average'])"/></div>
</div>

<div class="card mb-3">
    <div class="card-header"><h2>Daily sales</h2><span class="small text-muted">Invoiced vs. net (after returns)</span></div>
    <div class="card-body"><div class="chart-wrap"><canvas role="img" aria-label="Daily sales chart" data-chart="{{ json_encode(["type" => count($daily) > 45 ? "line" : "bar", "labels" => array_column($daily, "label"), "datasets" => [["label" => "Net sales", "data" => array_map("floatval", array_column($daily, "net"))], ["label" => "Returns", "data" => array_map("floatval", array_column($daily, "returns")), "color" => "#f79009"]]]) }}"></canvas></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card table-card h-100">
        <div class="card-header"><h2>By branch</h2></div>
        <div class="table-responsive"><table class="table table-stack"><thead><tr><th>Branch</th><th class="text-end">Invoices</th><th class="text-end">Returns</th><th class="text-end">Net</th></tr></thead><tbody>
            @foreach($byBranch as $b)<tr><td class="cell-primary cell-title">{{ $b['name'] }}</td><td data-label="Invoices" class="text-end">{{ $b['invoices'] }}</td><td data-label="Returns" class="text-end tabular text-danger">@money($b['returns'])</td><td data-label="Net" class="text-end tabular fw-semibold">@money($b['net'])</td></tr>@endforeach
        </tbody></table></div>
    </div></div>
    <div class="col-lg-6"><div class="card table-card h-100">
        <div class="card-header"><h2>By cashier</h2></div>
        @if(empty($byCashier))<x-empty icon="person" title="No sales in period"/>@else
        <div class="table-responsive"><table class="table table-stack"><thead><tr><th>Cashier</th><th class="text-end">Invoices</th><th class="text-end">Discounts</th><th class="text-end">Net</th></tr></thead><tbody>
            @foreach($byCashier as $c)<tr><td class="cell-primary cell-title">{{ $c['name'] }}</td><td data-label="Invoices" class="text-end">{{ $c['invoices'] }}</td><td data-label="Discounts" class="text-end tabular">@money($c['discounts'])</td><td data-label="Net" class="text-end tabular fw-semibold">@money($c['net'])</td></tr>@endforeach
        </tbody></table></div>@endif
    </div></div>
</div>

<div class="row g-3">
    <div class="col-lg-5"><div class="card table-card h-100">
        <div class="card-header"><h2>Top products</h2></div>
        @if(empty($top))<x-empty icon="trophy" title="No product sales"/>@else
        <table class="table table-stack"><thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead><tbody>
            @foreach($top as $p)<tr><td class="cell-primary cell-title">{{ $p['product_name'] }}</td><td data-label="Qty" class="text-end tabular">@qty($p['qty']) {{ $p['unit'] }}</td><td data-label="Revenue" class="text-end tabular fw-semibold">@money($p['revenue'])</td></tr>@endforeach
        </tbody></table>@endif
    </div></div>
    <div class="col-lg-7"><div class="card table-card h-100">
        <div class="card-header"><h2>Daily breakdown</h2></div>
        <div class="table-responsive" style="max-height: 420px"><table class="table table-sm table-stack mb-0"><thead class="sticky-top"><tr><th>Date</th><th class="text-end">Invoices</th><th class="text-end">Gross</th><th class="text-end">Discounts</th><th class="text-end">Returns</th><th class="text-end">Net</th></tr></thead><tbody>
            @foreach(array_reverse($daily) as $d)<tr><td class="cell-primary">{{ \Carbon\Carbon::parse($d['date'])->format('D d M') }}</td><td data-label="Invoices" class="text-end">{{ $d['invoices'] }}</td><td data-label="Gross" class="text-end tabular">@money($d['gross'])</td><td data-label="Discounts" class="text-end tabular">@money($d['discounts'])</td><td data-label="Returns" class="text-end tabular">@money($d['returns'])</td><td data-label="Net" class="text-end tabular fw-semibold">@money($d['net'])</td></tr>@endforeach
        </tbody>
        <tfoot class="fw-bold d-none d-md-table-footer-group"><tr><td>Total</td><td class="text-end">{{ $summary['invoices'] }}</td><td class="text-end tabular">@money($summary['gross'])</td><td class="text-end tabular">@money($summary['discounts'])</td><td class="text-end tabular">@money($summary['returns'])</td><td class="text-end tabular">@money($summary['net'])</td></tr></tfoot></table></div>
    </div></div>
</div>
<div class="card mt-3"><div class="card-body small text-muted">
    <strong class="text-dark">How these figures are calculated.</strong>
    Gross = Σ invoice subtotals. Net = Σ invoice totals (gross − discounts + tax) − refunds processed in the period. Returns are counted on the day they are processed.
    Sales are counted on the invoice date.
</div></div>
@endsection
