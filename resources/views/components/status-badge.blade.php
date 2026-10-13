@props(['status'])
@if ($status)
    <span {{ $attributes->class(['badge', 'badge-soft-'.$status->color()]) }}>{{ $status->label() }}</span>
@endif
