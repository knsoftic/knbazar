@extends('layouts.panel')

@php
    $u = auth()->user();
    $menu = [
        'Overview' => [
            ['admin.dashboard', 'bi-speedometer2', 'Dashboard', 'admin.dashboard', true, null],
            ['admin.reports.index', 'bi-bar-chart-line', 'Reports', 'admin.reports.*', $u->can('view_reports'), null],
        ],
        'Catalog' => [
            ['admin.products.index', 'bi-box-seam', 'Products', 'admin.products.*', $u->can('view_products'), $adminBadges['products'] ?: null],
            ['admin.categories.index', 'bi-folder2', 'Categories', 'admin.categories.*', $u->can('manage_categories'), null],
        ],
        'Sales' => [
            ['admin.orders.index', 'bi-receipt', 'Orders', 'admin.orders.*', $u->can('manage_orders'), null],
            ['admin.payments.index', 'bi-credit-card', 'Payments', 'admin.payments.*', $u->can('manage_payments'), $adminBadges['payments'] ?: null],
            ['admin.gateways.index', 'bi-wallet2', 'Payment Gateways', 'admin.gateways.*', $u->can('manage_payment_gateways'), null],
            ['admin.downloads.index', 'bi-download', 'Downloads', 'admin.downloads.*', $u->can('manage_downloads'), null],
        ],
        'People' => [
            ['admin.users.index', 'bi-people', 'Users', 'admin.users.*', $u->can('manage_users'), null],
            ['admin.publishers.index', 'bi-shop', 'Publishers', 'admin.publishers.*', $u->can('manage_publishers'), $adminBadges['publishers'] ?: null],
            ['admin.customers.index', 'bi-person-badge', 'Customers', 'admin.customers.*', $u->can('manage_users'), null],
            ['admin.admins.index', 'bi-shield-lock', 'Admins', 'admin.admins.*', $u->can('manage_admins'), null],
        ],
        'Content' => [
            ['admin.pages.index', 'bi-file-earmark-text', 'Pages', 'admin.pages.*', $u->can('manage_pages'), null],
            ['admin.banners.index', 'bi-images', 'Homepage Banners', 'admin.banners.*', $u->can('manage_homepage'), null],
            ['admin.messages.index', 'bi-envelope', 'Contact Messages', 'admin.messages.*', $u->can('manage_messages'), $adminBadges['messages'] ?: null],
        ],
        'System' => [
            ['admin.seo.edit', 'bi-search', 'SEO', 'admin.seo.*', $u->can('manage_seo'), null],
            ['admin.settings.edit', 'bi-gear', 'Settings', 'admin.settings.*', $u->can('manage_settings') || $u->can('manage_homepage'), null],
            ['admin.logs.index', 'bi-journal-code', 'System Logs', 'admin.logs.*', $u->can('view_logs'), null],
        ],
    ];
@endphp

@section('panel-label', $u->isSuperAdmin() ? 'Super Admin' : 'Admin')

@section('sidebar')
    @foreach ($menu as $heading => $items)
        @php $visible = array_filter($items, fn ($item) => $item[4]); @endphp
        @if ($visible)
            <div class="nav-heading">{{ $heading }}</div>
            @foreach ($visible as [$route, $icon, $label, $pattern, $allowed, $badge])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" title="{{ $label }}">
                    <i class="bi {{ $icon }}"></i><span class="label">{{ $label }}</span>
                    @if ($badge)<span class="badge text-bg-danger">{{ $badge }}</span>@endif
                </a>
            @endforeach
        @endif
    @endforeach
@endsection

@section('search')
    <form action="{{ route('admin.search') }}" method="GET" class="header-search search d-none d-sm-block" role="search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="form-control" placeholder="Search products, orders, users..." value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" aria-label="Admin search" maxlength="100">
    </form>
@endsection

@section('profile-links')
    @if ($u->isSuperAdmin())
        <li><a class="dropdown-item" href="{{ route('admin.admins.edit', $u) }}"><i class="bi bi-person-gear me-2"></i>Admin profile</a></li>
    @endif
@endsection
