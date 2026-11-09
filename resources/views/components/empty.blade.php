@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'text' => null])
<div class="empty-state">
    <div class="empty-icon"><i class="bi bi-{{ $icon }}"></i></div>
    <h3>{{ $title }}</h3>
    @if($text)<p class="mb-3">{{ $text }}</p>@endif
    {{ $slot }}
</div>
