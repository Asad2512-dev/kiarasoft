@props(['label', 'value', 'icon' => 'graph-up', 'tint' => 'brand', 'foot' => null, 'href' => null])
<div class="card stat-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <span class="stat-label">{{ $label }}</span>
            <span class="stat-icon tint-{{ $tint }}"><i class="bi bi-{{ $icon }}"></i></span>
        </div>
        <div class="stat-value">{{ $value }}</div>
        @if($foot || $slot->isNotEmpty())<div class="stat-foot">{{ $foot }}{{ $slot }}</div>@endif
        @if($href)<a href="{{ $href }}" class="stretched-link" aria-label="{{ $label }}"></a>@endif
    </div>
</div>
