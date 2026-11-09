@extends('layouts.app', ['title' => 'Return '.$return->return_no])
@section('content')
<div class="page-head">
    <div><a href="{{ route('returns.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Returns</a>
        <h1 class="mono mt-1">{{ $return->return_no }}</h1><p>{{ $return->created_at->format('d M Y, h:i A') }} · by {{ $return->user->name }} · {{ $return->branch->name }}</p></div>
    <div class="actions"><a href="{{ route('sales.show', $return->sale) }}" class="btn btn-white"><i class="bi bi-receipt"></i> Original invoice</a></div>
</div>
<div class="row g-3">
    <div class="col-lg-8"><div class="card table-card">
        <div class="table-responsive"><table class="table table-stack">
            <thead><tr><th>Product</th><th class="text-end">Qty returned</th><th>Stock</th><th class="text-end">Refund</th></tr></thead>
            <tbody>@foreach($return->items as $i)
                <tr><td class="cell-primary cell-title">{{ $i->saleItem->product_name }}</td><td data-label="Qty" class="text-end tabular">@qty($i->quantity) {{ $i->saleItem->unit }}</td>
                    <td data-label="Stock">@if($i->restocked)<span class="badge-soft badge-soft-success">Restocked</span>@else<span class="badge-soft badge-soft-secondary">Not restocked</span>@endif</td>
                    <td data-label="Refund" class="text-end tabular fw-semibold">@money($i->amount)</td></tr>
            @endforeach</tbody>
        </table></div>
    </div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body">
        <div class="small-caps">Total refund</div><div class="display-6 fw-bold text-danger tabular">@money($return->refund_amount)</div>
        <hr><dl class="dl-grid mb-0"><dt>Invoice</dt><dd class="mono">{{ $return->sale->invoice_no }}</dd><dt>Reason</dt><dd>{{ $return->reason }}</dd><dt>Invoice status</dt><dd>{{ $return->sale->status->label() }}</dd></dl>
    </div></div></div>
</div>
@endsection
