@php $logo = setting('site_logo'); @endphp
<a href="{{ route('home') }}" class="brand" aria-label="{{ setting('site_name') }} home">
    @if ($logo)
        <img src="{{ storage_url($logo) }}" alt="{{ setting('site_name') }}" height="40">
    @else
        <span class="brand-mark"><i class="bi bi-bag-heart-fill"></i></span>
        <span class="brand-text">{{ setting('site_name') }}</span>
    @endif
</a>
