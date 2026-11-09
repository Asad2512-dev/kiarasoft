@extends('layouts.app', ['title' => 'Stock movements'])
@section('content')
<div class="page-head">
    <div><h1>Stock movements</h1><p>Complete audit trail of every stock change{{ $product ? ' for '.$product->name : '' }}.</p></div>
    <div class="actions">@if($product)<a href="{{ route('inventory.movements') }}" class="btn btn-white"><i class="bi bi-x"></i> Clear product filter</a>@endif</div>
</div>
<div class="card table-card">
    <div class="card-header">
        <form class="filter-bar w-100" method="GET">
            @if($product)<input type="hidden" name="product" value="{{ $product->id }}">@endif
            @if($branches->count() > 1)<select name="branch" class="form-select" data-autosubmit aria-label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
            <select name="type" class="form-select" data-autosubmit aria-label="Type"><option value="">All types</option>@foreach(\App\Enums\MovementType::cases() as $t)<option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>@endforeach</select>
            <x-date-filter :from="$from" :to="$to"/>
            <button class="btn btn-white" data-no-lock>Filter</button>
        </form>
    </div>
    @include('inventory._movements_table', ['movements' => $movements])
    @if($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
</div>
@endsection
