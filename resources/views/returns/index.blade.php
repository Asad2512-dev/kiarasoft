@extends('layouts.app', ['title' => 'Returns'])
@section('content')
<div class="page-head"><div><h1>Sales returns</h1><p>Controlled refunds against original invoices.</p></div></div>
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-5"><label for="invoice" class="form-label">Start a return: find the original invoice</label>
                <input id="invoice" name="invoice" value="{{ request('invoice') }}" class="form-control form-control-lg mono" placeholder="e.g. INV-G11-20261109-0001" required></div>
            <div class="col-auto"><button class="btn btn-primary btn-lg" data-no-lock><i class="bi bi-search"></i> Find invoice</button></div>
        </form>
        <div class="small text-muted mt-2">You can also open any invoice from <a href="{{ route('sales.index') }}">Sales</a> and choose <em>Process return</em>.</div>
    </div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Return or invoice no." aria-label="Search returns"></div>
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <x-date-filter :from="$from" :to="$to"/>
            <button class="btn btn-white" data-no-lock>Filter</button>
        </form>
    </div>
    @if($returns->isEmpty())
        <x-empty icon="arrow-counterclockwise" title="No returns in this period"/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Return no.</th><th>Invoice</th><th>Date</th><th>Branch</th><th>By</th><th>Reason</th><th class="text-end">Refund</th></tr></thead>
        <tbody>@foreach($returns as $r)
            <tr>
                <td class="cell-primary"><a href="{{ route('returns.show', $r) }}" class="mono cell-title">{{ $r->return_no }}</a></td>
                <td data-label="Invoice"><a href="{{ route('sales.show', $r->sale) }}" class="mono">{{ $r->sale->invoice_no }}</a></td>
                <td data-label="Date">{{ $r->created_at->format('d M Y, h:i A') }}</td>
                <td data-label="Branch">{{ $r->branch->name }}</td>
                <td data-label="By">{{ $r->user->name }}</td>
                <td data-label="Reason" class="text-truncate" style="max-width: 220px">{{ $r->reason }}</td>
                <td data-label="Refund" class="text-end text-danger fw-semibold tabular">@money($r->refund_amount)</td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $returns->links() }}</div>
    @endif
</div>
@endsection
