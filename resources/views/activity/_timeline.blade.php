@php
    $iconFor = fn ($a) => match (true) {
        str_starts_with($a, 'sale.returned') => ['arrow-counterclockwise', 'amber'],
        str_starts_with($a, 'sale.') => ['receipt', 'brand'],
        str_starts_with($a, 'inventory.') => ['boxes', 'blue'],
        str_starts_with($a, 'product.'), str_starts_with($a, 'category.') => ['box-seam', 'violet'],
        str_starts_with($a, 'auth.failed') => ['shield-exclamation', 'red'],
        str_starts_with($a, 'auth.') => ['box-arrow-in-right', 'gray'],
        str_starts_with($a, 'employee.'), str_starts_with($a, 'profile.') => ['person-badge', 'blue'],
        str_starts_with($a, 'system.') => ['database', 'red'],
        default => ['dot', 'gray'],
    };
@endphp
@if($logs->isEmpty())
    <x-empty icon="clock-history" title="No activity yet"/>
@else
<ul class="timeline">
    @foreach($logs as $log)
        @php [$ic, $tint] = $iconFor($log->action); @endphp
        <li>
            <span class="t-icon tint-{{ $tint }}"><i class="bi bi-{{ $ic }}"></i></span>
            <div class="min-w-0">
                <div class="t-text">{{ $log->description }}</div>
                <div class="t-meta">{{ $log->user?->name ?? 'System' }} · <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d M Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time></div>
            </div>
        </li>
    @endforeach
</ul>
@endif
