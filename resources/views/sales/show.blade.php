@extends('layouts.app', ['title' => 'Invoice '.$sale->invoice_no])
@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('sales.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Sales</a>
        <h1 class="mono mt-1">{{ $sale->invoice_no }} <span class="badge-soft badge-soft-{{ $sale->status->badge() }} align-middle" style="font-family: var(--bs-font-sans-serif)">{{ $sale->status->label() }}</span></h1>
        <p>{{ $sale->sold_at->format('l, d M Y \a\t h:i A') }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('sales.receipt', ['sale' => $sale, 'print' => 1]) }}" target="_blank" class="btn btn-white"><i class="bi bi-printer"></i> {{ $sale->returns->isEmpty() ? 'Print' : 'Reprint' }} receipt</a>
        @can('refund', $sale)@unless($sale->isFullyReturned())<a href="{{ route('returns.create', $sale) }}" class="btn btn-soft-danger"><i class="bi bi-arrow-counterclockwise"></i> Process return</a>@endunless @endcan
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header"><h2>Items</h2><span class="small text-muted">Prices as charged at time of sale</span></div>
            <div class="table-responsive"><table class="table table-stack">
                <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Returned</th><th class="text-end">Line total</th></tr></thead>
                <tbody>
                @foreach($sale->items as $i)
                    <tr>
                        <td class="cell-primary"><div class="cell-title">{{ $i->product_name }}</div><div class="cell-sub mono">{{ $i->sku }}</div></td>
                        <td data-label="Qty" class="text-end tabular">@qty($i->quantity) {{ $i->unit }}</td>
                        <td data-label="Unit price" class="text-end tabular">@money($i->unit_price)</td>
                        <td data-label="Returned" class="text-end tabular {{ (float)$i->returned_quantity > 0 ? 'text-danger' : 'text-muted' }}">@qty($i->returned_quantity)</td>
                        <td data-label="Line total" class="text-end fw-semibold tabular">@money($i->line_total)</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="card-body border-top">
                <div class="ms-auto" style="max-width: 320px">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span class="tabular">@money($sale->subtotal)</span></div>
                    @if((float)$sale->discount_amount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Discount {{ $sale->discount_type === 'percent' ? '('.(float)$sale->discount_value.'%)' : '' }}</span><span class="tabular text-danger">-@money($sale->discount_amount)</span></div>@endif
                    @if((float)$sale->tax_amount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Tax ({{ (float)$sale->tax_rate }}%)</span><span class="tabular">@money($sale->tax_amount)</span></div>@endif
                    <div class="d-flex justify-content-between py-2 border-top mt-1 fw-bold fs-5"><span>Total</span><span class="tabular">@money($sale->total)</span></div>
                    @if((float)$sale->refunded_total > 0)
                        <div class="d-flex justify-content-between py-1 text-danger"><span>Refunded</span><span class="tabular">-@money($sale->refunded_total)</span></div>
                        <div class="d-flex justify-content-between py-1 fw-semibold"><span>Net</span><span class="tabular">@money($sale->netTotal())</span></div>
                    @endif
                </div>
            </div>
        </div>
        @if($sale->returns->isNotEmpty())
        <div class="card table-card mt-3">
            <div class="card-header"><h2>Returns</h2></div>
            <div class="table-responsive"><table class="table table-stack">
                <thead><tr><th>Return no.</th><th>Date</th><th>Processed by</th><th>Reason</th><th class="text-end">Refund</th></tr></thead>
                <tbody>@foreach($sale->returns as $r)
                    <tr><td class="cell-primary"><a href="{{ route('returns.show', $r) }}" class="mono cell-title">{{ $r->return_no }}</a></td><td data-label="Date">{{ $r->created_at->format('d M Y, h:i A') }}</td><td data-label="By">{{ $r->user->name }}</td><td data-label="Reason">{{ $r->reason }}</td><td data-label="Refund" class="text-end tabular text-danger fw-semibold">@money($r->refund_amount)</td></tr>
                @endforeach</tbody>
            </table></div>
        </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header"><h2>Details</h2></div><div class="card-body">
            <dl class="dl-grid">
                <dt>Branch</dt><dd>{{ $sale->branch->name }}</dd>
                <dt>Cashier</dt><dd>{{ $sale->cashier->name }}</dd>
                <dt>Customer</dt><dd>{{ $sale->customer_name ?: 'Walk-in' }}</dd>
                <dt>Items</dt><dd>{{ $sale->items->count() }}</dd>
            </dl>
        </div></div>
        <div class="card"><div class="card-header"><h2>Payment</h2><span class="badge-soft badge-soft-success text-uppercase">{{ $sale->payment->method }}</span></div><div class="card-body">
            <dl class="dl-grid">
                <dt>Amount due</dt><dd class="tabular">@money($sale->payment->amount)</dd>
                <dt>Cash received</dt><dd class="tabular">@money($sale->payment->amount_tendered)</dd>
                <dt>Change given</dt><dd class="tabular">@money($sale->payment->change_due)</dd>
            </dl>
        </div></div>
    </div>
</div>
@endsection
