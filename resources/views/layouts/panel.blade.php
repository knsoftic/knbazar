<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php $seo ??= app(\App\Services\SeoService::class)->defaults(trim($__env->yieldContent('title')) ?: 'Dashboard')->noindex(); @endphp
    @include('partials.head')
</head>
<body class="panel-body">
    @php $user = auth()->user(); @endphp

    <aside class="panel-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="panelSidebar" aria-labelledby="panelSidebarLabel">
        <div class="sidebar-head d-flex align-items-center justify-content-between">
            <span id="panelSidebarLabel">@include('partials.brand')</span>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#panelSidebar" aria-label="Close menu"></button>
        </div>
        <div class="offcanvas-body d-block p-0 pb-4">
            <div class="px-3 pt-3 small text-uppercase" style="color:#64748b; letter-spacing:.08em">@yield('panel-label')</div>
            <nav class="nav flex-column" aria-label="Dashboard">
                @yield('sidebar')
            </nav>
        </div>
    </aside>

    <div class="panel-main">
        <header class="panel-topbar">
            <button class="btn btn-light btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#panelSidebar" aria-controls="panelSidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <button class="btn btn-light btn-icon d-none d-lg-inline-flex" type="button" data-sidebar-collapse aria-label="Collapse sidebar"><i class="bi bi-layout-sidebar"></i></button>

            @hasSection('search')
                @yield('search')
            @else
                <div class="flex-grow-1"></div>
            @endif

            <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
                <a href="{{ route('home') }}" class="btn btn-light btn-sm d-none d-md-inline-flex" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i> View site</a>
                <div class="dropdown">
                    <a href="#" class="header-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if ($unreadNotifications)<span class="count">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notif-menu">
                        @include('partials.notification-list', ['items' => $latestNotifications])
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                        @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" class="avatar" alt="">@else<span class="avatar">{{ $user->initials() }}</span>@endif
                        <span class="d-none d-md-block text-start lh-sm">
                            <span class="d-block small fw-semibold text-ink">{{ $user->name }}</span>
                            <span class="d-block small-xs text-muted-2">{{ $user->role?->name }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @yield('profile-links')
                        <li><a class="dropdown-item" href="{{ route('account.index', $user->isAdmin() ? ['customer' => 1] : []) }}"><i class="bi bi-person me-2"></i>My account</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-shop-window me-2"></i>Marketplace</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="panel-content" id="main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>

    @include('partials.modals')
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
</body>
</html>
