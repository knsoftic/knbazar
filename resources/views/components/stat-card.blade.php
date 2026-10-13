@props(['label', 'value', 'icon' => 'bi-graph-up', 'color' => 'primary', 'href' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['stat-card']) }}>
@else
    <div {{ $attributes->class(['stat-card']) }}>
@endif
    <div class="icon icon-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
    <div class="min-w-0">
        <div class="value">{{ $value }}</div>
        <div class="label">{{ $label }}</div>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif
