@if($movements->isEmpty())
    <x-empty icon="arrow-left-right" title="No stock movements"/>
@else
<div class="table-responsive"><table class="table table-hover table-stack">
    <thead><tr><th>Date</th>@if($showProduct ?? true)<th>Product</th>@endif<th>Branch</th><th>Type</th><th class="text-end">Change</th><th class="text-end">Before → After</th><th>By / reason</th></tr></thead>
    <tbody>@foreach($movements as $m)
        <tr>
            <td data-label="Date" class="text-nowrap">{{ $m->created_at->format('d M, h:i A') }}</td>
            @if($showProduct ?? true)<td class="cell-primary"><span class="cell-title">{{ $m->product->name }}</span><div class="cell-sub mono">{{ $m->product->sku }}</div></td>@endif
            <td data-label="Branch">{{ $m->branch->name }}</td>
            <td data-label="Type"><span class="badge-soft badge-soft-{{ $m->type->badge() }}">{{ $m->type->label() }}</span></td>
            <td data-label="Change" class="text-end tabular fw-semibold {{ (float)$m->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ (float)$m->quantity_change > 0 ? '+' : '' }}@qty($m->quantity_change)</td>
            <td data-label="Before → After" class="text-end tabular text-muted text-nowrap">@qty($m->quantity_before) → <strong class="text-dark">@qty($m->quantity_after)</strong></td>
            <td data-label="By"><div>{{ $m->user?->name ?? 'System' }}</div>@if($m->reason)<div class="cell-sub text-truncate" style="max-width: 240px" title="{{ $m->reason }}">{{ $m->reason }}</div>@endif</td>
        </tr>
    @endforeach</tbody>
</table></div>
@endif
