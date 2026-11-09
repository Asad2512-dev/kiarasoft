@extends('layouts.app', ['title' => auth()->user()->isCashier() ? 'My sales' : 'Sales & invoices'])
@section('content')
<div class="page-head">
    <div><h1>{{ auth()->user()->isCashier() ? 'My sales' : 'Sales & invoices' }}</h1><p>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p></div>
    <div class="actions">@can('pos.use')<a href="{{ route('pos.index') }}" class="btn btn-primary"><i class="bi bi-cart3"></i> New sale</a>@endcan</div>
</div>
<div class="row g-3 mb-3">
    <div class="col-4"><x-stat label="Invoices" :value="number_format($totals->c)" icon="receipt" tint="blue"/></div>
    <div class="col-4"><x-stat label="Invoiced" :value="\App\Support\Money::format($totals->t)" icon="cash" tint="brand"/></div>
    <div class="col-4"><x-stat label="Refunded" :value="\App\Support\Money::format($totals->r)" icon="arrow-counterclockwise" tint="amber"/></div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Invoice no. or customer" aria-label="Search"></div>
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            @if($cashiers->isNotEmpty())<select name="cashier" class="form-select" data-autosubmit aria-label="Cashier"><option value="">All cashiers</option>@foreach($cashiers as $c)<option value="{{ $c->id }}" @selected(request('cashier') == $c->id)>{{ $c->name }}</option>@endforeach</select>@endif
            <select name="status" class="form-select" data-autosubmit aria-label="Status"><option value="">Any status</option>@foreach(\App\Enums\SaleStatus::cases() as $st)<option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>@endforeach</select>
            <x-date-filter :from="$from" :to="$to"/>
            <button class="btn btn-white" data-no-lock><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
    @if($sales->isEmpty())
        <x-empty icon="receipt" title="No invoices found" text="Try widening the date range or clearing filters."/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Invoice</th><th>Date</th><th>Branch</th><th>Cashier</th><th class="text-center">Items</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
        <tbody>
        @foreach($sales as $s)
            <tr>
                <td class="cell-primary"><a href="{{ route('sales.show', $s) }}" class="cell-title mono">{{ $s->invoice_no }}</a>@if($s->customer_name)<div class="cell-sub">{{ $s->customer_name }}</div>@endif</td>
                <td data-label="Date">{{ $s->sold_at->format('d M Y, h:i A') }}</td>
                <td data-label="Branch">{{ $s->branch->name }}</td>
                <td data-label="Cashier">{{ $s->cashier->name }}</td>
                <td data-label="Items" class="text-md-center">{{ $s->items_count }}</td>
                <td data-label="Status"><span class="badge-soft badge-soft-{{ $s->status->badge() }}">{{ $s->status->label() }}</span></td>
                <td data-label="Total" class="text-end fw-semibold tabular">@money($s->total)@if((float)$s->refunded_total > 0)<div class="cell-sub text-danger">-@money($s->refunded_total)</div>@endif</td>
                <td class="cell-actions text-end text-nowrap">
                    <a href="{{ route('sales.receipt', ['sale' => $s, 'print' => 1]) }}" target="_blank" class="btn btn-sm btn-white btn-icon" title="Print receipt" aria-label="Print receipt {{ $s->invoice_no }}"><i class="bi bi-printer"></i></a>
                    <a href="{{ route('sales.show', $s) }}" class="btn btn-sm btn-white">View</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="card-footer">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
