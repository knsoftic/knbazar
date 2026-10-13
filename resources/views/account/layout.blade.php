@extends('layouts.app')

@php
    $accountLinks = [
        ['account.index', 'bi-grid', 'Overview', ['account.index']],
        ['orders.index', 'bi-receipt', 'Orders', ['orders.*']],
        ['downloads.index', 'bi-download', 'Downloads', ['downloads.index']],
        ['account.purchases', 'bi-bag-check', 'Purchased products', ['account.purchases']],
        ['account.payments', 'bi-credit-card', 'Payment history', ['account.payments']],
        ['notifications.index', 'bi-bell', 'Notifications', ['notifications.*']],
        ['account.profile', 'bi-person', 'Profile', ['account.profile']],
    ];
@endphp

@section('content')
    <div class="container py-4">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            @if (auth()->user()->avatarUrl())
                                <img src="{{ auth()->user()->avatarUrl() }}" class="avatar" alt="">
                            @else
                                <span class="avatar">{{ auth()->user()->initials() }}</span>
                            @endif
                            <div class="min-w-0">
                                <div class="fw-semibold text-ink text-truncate">{{ auth()->user()->name }}</div>
                                <div class="small text-muted-2 text-truncate">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <nav class="list-group account-nav" aria-label="Account">
                            @foreach ($accountLinks as [$route, $icon, $label, $patterns])
                                <a href="{{ route($route) }}" class="list-group-item list-group-item-action {{ request()->routeIs(...$patterns) ? 'active' : '' }}"><i class="bi {{ $icon }}"></i> {{ $label }}</a>
                            @endforeach
                            @if (auth()->user()->isPublisher())
                                <a href="{{ route('publisher.dashboard') }}" class="list-group-item list-group-item-action"><i class="bi bi-shop"></i> Publisher dashboard</a>
                            @elseif (auth()->user()->isCustomer() && setting('publisher_registration_enabled'))
                                <a href="{{ route('register.publisher') }}" class="list-group-item list-group-item-action"><i class="bi bi-rocket-takeoff"></i> Become a publisher</a>
                            @endif
                        </nav>
                    </div>
                </div>
            </aside>
            <div class="col-lg-9 min-w-0">
                @yield('account')
            </div>
        </div>
    </div>
@endsection
