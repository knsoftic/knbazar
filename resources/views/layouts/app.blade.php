<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    <a class="visually-hidden-focusable btn btn-primary position-absolute m-2" href="#main">Skip to content</a>

    @include('partials.header')

    <main id="main">
        @hasSection('page-header')
            @yield('page-header')
        @endif

        @include('partials.flash', ['wrap' => true])

        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.modals')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
</body>
</html>
