@props(['active', 'on' => 'Active', 'off' => 'Inactive'])
<span class="badge-soft badge-soft-{{ $active ? 'success' : 'secondary' }}"><span class="status-dot bg-{{ $active ? 'success' : 'secondary' }}"></span>{{ $active ? $on : $off }}</span>
