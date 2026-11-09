@props(['status'])
@php $map = ['out' => ['danger', 'Out of stock'], 'low' => ['warning', 'Low stock'], 'ok' => ['success', 'In stock']]; [$c, $l] = $map[$status] ?? $map['ok']; @endphp
<span class="badge-soft badge-soft-{{ $c }}"><span class="status-dot bg-{{ $c }}"></span>{{ $l }}</span>
