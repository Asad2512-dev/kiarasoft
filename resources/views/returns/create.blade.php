@extends('layouts.app', ['title' => 'Return for '.$sale->invoice_no])
@section('content')
<div class="page-head">
    <div><a href="{{ route('sales.show', $sale) }}" class="small text-muted"><i class="bi bi-arrow-left"></i> {{ $sale->invoice_no }}</a>
        <h1 class="mt-1">Process return</h1><p>Invoice <span class="mono">{{ $sale->invoice_no }}</span> · {{ $sale->sold_at->format('d M Y') }} · {{ $sale->branch->name }} · paid @money($sale->total)</p></div>
</div>
@if($sale->isFullyReturned())
    <div class="card"><x-empty icon="check2-all" title="Fully returned" text="All items on this invoice have already been returned."/></div>
@else
<form method="POST" action="{{ route('returns.store', $sale) }}" x-data="{ q: {} }" data-confirm="Confirm this return and refund? This cannot be undone.">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header"><h2>Select items to return</h2><span class="small text-muted">Refunds account for any discount/tax on the invoice</span></div>
                <div class="table-responsive"><table class="table table-stack">
                    <thead><tr><th>Product</th><th class="text-end">Sold</th><th class="text-end">Already returned</th><th class="text-end">Unit price</th><th style="width: 160px">Return qty</th></tr></thead>
                    <tbody>@foreach($sale->items as $i)
                        @php $max = (float) $i->returnableQuantity(); $dec = in_array($i->unit, ['kg','g','l','ml']); @endphp
                        <tr class="{{ $max <= 0 ? 'opacity-50' : '' }}">
                            <td class="cell-primary"><div class="cell-title">{{ $i->product_name }}</div><div class="cell-sub mono">{{ $i->sku }}</div></td>
                            <td data-label="Sold" class="text-end tabular">@qty($i->quantity) {{ $i->unit }}</td>
                            <td data-label="Returned" class="text-end tabular">@qty($i->returned_quantity)</td>
                            <td data-label="Unit price" class="text-end tabular">@money($i->unit_price)</td>
                            <td data-label="Return qty">
                                @if($max > 0)
                                <div class="input-group input-group-sm">
                                    <input type="number" name="quantities[{{ $i->id }}]" value="{{ old('quantities.'.$i->id) }}" min="0" max="{{ $max }}" step="{{ $dec ? '0.001' : '1' }}" class="form-control text-end tabular" aria-label="Return quantity for {{ $i->product_name }}" placeholder="0">
                                    <button type="button" class="btn btn-white" onclick="this.previousElementSibling.value='{{ \App\Support\Money::qty($max) }}'" data-no-lock>Max</button>
                                </div>
                                <div class="small text-muted mt-1">max @qty($max)</div>
                                @else<span class="small text-muted">Fully returned</span>@endif
                            </td>
                        </tr>
                    @endforeach</tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <div class="mb-3"><label for="reason" class="form-label">Reason for return</label>
                    <select class="form-select mb-2" onchange="if(this.value) document.getElementById('reason').value = this.value" aria-label="Common reasons">
                        <option value="">Choose a common reason…</option><option>Damaged / defective item</option><option>Expired product</option><option>Wrong item billed</option><option>Customer changed mind</option>
                    </select>
                    <input id="reason" name="reason" value="{{ old('reason') }}" class="form-control" required maxlength="255"></div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="restock" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="restock" name="restock" value="1" @checked(old('restock', true))>
                    <label class="form-check-label" for="restock">Return items to sellable stock</label>
                    <div class="small text-muted">Turn off for damaged/expired goods that cannot be resold.</div>
                </div>
                <button class="btn btn-danger w-100 btn-lg"><i class="bi bi-arrow-counterclockwise"></i> Record return &amp; refund</button>
            </div></div>
        </div>
    </div>
</form>
@endif
@endsection
