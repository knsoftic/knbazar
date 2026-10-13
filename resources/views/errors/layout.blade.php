@php
    // Error pages must render even when the database is unavailable.
    try { $siteName = setting('site_name') ?: config('app.name'); } catch (\Throwable) { $siteName = config('app.name'); }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') - @yield('title') | {{ $siteName }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
    <main class="d-flex align-items-center justify-content-center text-center px-3" style="min-height: 100vh">
        <div style="max-width: 520px">
            <a href="{{ url('/') }}" class="brand mb-4 d-inline-flex"><span class="brand-mark"><i class="bi bi-bag-heart-fill"></i></span> {{ $siteName }}</a>
            <div class="display-1 fw-bold text-primary mb-2">@yield('code')</div>
            <h1 class="h3 mb-3">@yield('title')</h1>
            <p class="text-muted-2 mb-4">@yield('message')</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> Go home</a>
                <a href="javascript:history.back()" class="btn btn-light"><i class="bi bi-arrow-left"></i> Go back</a>
                @yield('actions')
            </div>
        </div>
    </main>
</body>
</html>
