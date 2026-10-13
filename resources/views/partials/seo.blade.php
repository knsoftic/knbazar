@php
    /** @var \App\Support\SeoData $seo */
    $seo ??= app(\App\Services\SeoService::class)->defaults();
    $siteName = setting('site_name');
@endphp
<title>{{ $seo->title }}</title>
@if ($seo->description)<meta name="description" content="{{ $seo->description }}">@endif
@if ($seo->keywords)<meta name="keywords" content="{{ $seo->keywords }}">@endif
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->canonical)<link rel="canonical" href="{{ $seo->canonical }}">@endif

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->ogTitle ?? $seo->title }}">
@if ($seo->ogDescription ?? $seo->description)<meta property="og:description" content="{{ $seo->ogDescription ?? $seo->description }}">@endif
@if ($seo->canonical)<meta property="og:url" content="{{ $seo->canonical }}">@endif
@if ($seo->ogImage)<meta property="og:image" content="{{ $seo->ogImage }}">@endif
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

<meta name="twitter:card" content="{{ $seo->ogImage ? $seo->twitterCard : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->ogTitle ?? $seo->title }}">
@if ($seo->ogDescription ?? $seo->description)<meta name="twitter:description" content="{{ $seo->ogDescription ?? $seo->description }}">@endif
@if ($seo->ogImage)<meta name="twitter:image" content="{{ $seo->ogImage }}">@endif
@if (setting('seo_twitter_handle'))<meta name="twitter:site" content="{{ setting('seo_twitter_handle') }}">@endif

@if (setting('seo_google_verification'))<meta name="google-site-verification" content="{{ setting('seo_google_verification') }}">@endif
@if (setting('seo_bing_verification'))<meta name="msvalidate.01" content="{{ setting('seo_bing_verification') }}">@endif

@foreach ($seo->jsonLd as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
