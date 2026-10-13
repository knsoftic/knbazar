@extends('layouts.panel')

@php
    $u = auth()->user();
    $menu = [
        ['publisher.dashboard', 'bi-speedometer2', 'Dashboard', 'publisher.dashboard'],
        ['publisher.products.index', 'bi-box-seam', 'My Products', 'publisher.products.index|publisher.products.show|publisher.products.edit'],
        ['publisher.products.create', 'bi-plus-circle', 'Add Product', 'publisher.products.create'],
        ['publisher.orders', 'bi-receipt', 'Orders', 'publisher.orders'],
        ['notifications.index', 'bi-bell', 'Notifications', 'notifications.*'],
        ['publisher.profile', 'bi-person-gear', 'Profile', 'publisher.profile'],
    ];
@endphp

@section('panel-label', 'Publisher')

@section('sidebar')
    <div class="nav-heading">Menu</div>
    @foreach ($menu as [$route, $icon, $label, $pattern])
        <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(...explode('|', $pattern)) ? 'active' : '' }}" title="{{ $label }}">
            <i class="bi {{ $icon }}"></i><span class="label">{{ $label }}</span>
        </a>
    @endforeach
    @if ($u->username)
        <div class="nav-heading">Storefront</div>
        <a href="{{ route('publishers.show', $u->username) }}" class="nav-link" target="_blank" title="Public profile"><i class="bi bi-shop-window"></i><span class="label">Public profile</span></a>
    @endif
    <div class="nav-heading">Account</div>
    <a href="{{ route('orders.index') }}" class="nav-link" title="My purchases"><i class="bi bi-bag"></i><span class="label">My purchases</span></a>
@endsection

@section('search')
    <form action="{{ route('publisher.products.index') }}" method="GET" class="header-search search d-none d-sm-block" role="search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="form-control" placeholder="Search my products..." value="{{ request()->routeIs('publisher.products.index') ? request('q') : '' }}" aria-label="Search my products" maxlength="100">
    </form>
@endsection
