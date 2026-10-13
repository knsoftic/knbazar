@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'text' => null])
<div {{ $attributes->class(['empty-state']) }}>
    <i class="bi {{ $icon }}"></i>
    <h3 class="h6 text-ink mb-1">{{ $title }}</h3>
    @if ($text)<p class="mb-3 small">{{ $text }}</p>@endif
    {{ $slot }}
</div>
