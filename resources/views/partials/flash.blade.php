@php
    $types = [
        'success' => ['success', 'bi-check-circle'],
        'status' => ['success', 'bi-check-circle'],
        'error' => ['danger', 'bi-exclamation-octagon'],
        'warning' => ['warning', 'bi-exclamation-triangle'],
        'info' => ['info', 'bi-info-circle'],
    ];
    $items = [];
    foreach ($types as $key => [$type, $icon]) {
        if (session($key)) {
            $items[] = [$type, $icon, session($key)];
        }
    }
    // Errors that are not tied to a visible form field.
    foreach (['cart', 'payment', 'submit', 'status', 'reason', 'download', 'ids', 'action'] as $key) {
        foreach ($errors->get($key) as $message) {
            $items[] = ['danger', 'bi-exclamation-octagon', $message];
        }
    }
    $wrap ??= false;
@endphp
@if ($items)
    @if ($wrap)<div class="container pt-3">@endif
    @foreach ($items as [$type, $icon, $message])
        <div class="alert alert-{{ $type }} alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
            <i class="bi {{ $icon }} mt-1"></i>
            <div>{{ $message }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
    @if ($wrap)</div>@endif
@endif
