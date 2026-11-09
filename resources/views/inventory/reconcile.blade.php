@extends('layouts.app', ['title' => 'Inventory reconciliation'])
@section('content')
<div class="page-head">
    <div><a href="{{ route('inventory.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Inventory</a><h1 class="mt-1">Inventory reconciliation</h1>
        <p>Verifies that each stock level equals the sum of its recorded movements.</p></div>
    <form class="actions" method="GET">@if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif</form>
</div>
@if($mismatches->isEmpty())
    <div class="card"><x-empty icon="patch-check" title="All {{ number_format($checked) }} stock lines reconcile" text="Every stock level matches its movement history exactly."/></div>
@else
    <div class="alert alert-danger">{{ $mismatches->count() }} of {{ $checked }} stock lines do not match their movement history. Investigate and correct with a count adjustment.</div>
    <div class="card table-card"><table class="table table-stack"><thead><tr><th>Product</th><th>Branch</th><th class="text-end">Stock</th><th class="text-end">Σ movements</th></tr></thead><tbody>
        @foreach($mismatches as $m)<tr><td class="cell-primary cell-title">{{ $m->product }}</td><td data-label="Branch">{{ $m->branch }}</td><td data-label="Stock" class="text-end tabular">@qty($m->quantity)</td><td data-label="Movements" class="text-end tabular">@qty($m->moved)</td></tr>@endforeach
    </tbody></table></div>
@endif
@endsection
