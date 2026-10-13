@extends('layouts.admin')

@section('title', 'Search')

@section('content')
    <div class="panel-title"><div><h1>Search results</h1><p>{{ $term !== '' ? $total.' result(s) for "'.$term.'"' : 'Type at least 2 characters in the search box.' }}</p></div></div>

    <form method="GET" action="{{ route('admin.search') }}" class="mb-4 d-sm-none">
        <input type="search" name="q" value="{{ $term }}" class="form-control" placeholder="Search..." aria-label="Search">
    </form>

    <div class="row g-4">
        @if ($results['products']->isNotEmpty())
            <div class="col-xl-6"><div class="card h-100"><div class="card-header">Products</div><div class="list-group list-group-flush">
                @foreach ($results['products'] as $product)
                    <a href="{{ route('admin.products.show', $product) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                        <span class="text-truncate"><span class="fw-semibold">#{{ $product->id }}</span> {{ $product->name }} <span class="small text-muted-2">by {{ $product->user?->name }}</span></span>
                        <x-status-badge :status="$product->status" />
                    </a>
                @endforeach
            </div></div></div>
        @endif
        @if ($results['orders']->isNotEmpty())
            <div class="col-xl-6"><div class="card h-100"><div class="card-header">Orders</div><div class="list-group list-group-flush">
                @foreach ($results['orders'] as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                        <span class="text-truncate"><span class="fw-semibold">{{ $order->order_number }}</span> &middot; {{ $order->customer_name }}</span>
                        <x-status-badge :status="$order->status" />
                    </a>
                @endforeach
            </div></div></div>
        @endif
        @if ($results['payments']->isNotEmpty())
            <div class="col-xl-6"><div class="card h-100"><div class="card-header">Payments</div><div class="list-group list-group-flush">
                @foreach ($results['payments'] as $payment)
                    <a href="{{ route('admin.payments.show', $payment) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                        <span class="text-truncate">#{{ $payment->id }} &middot; <span class="mono">{{ $payment->transaction_id }}</span> &middot; {{ $payment->order?->order_number }}</span>
                        <x-status-badge :status="$payment->status" />
                    </a>
                @endforeach
            </div></div></div>
        @endif
        @if ($results['users']->isNotEmpty())
            <div class="col-xl-6"><div class="card h-100"><div class="card-header">Users</div><div class="list-group list-group-flush">
                @foreach ($results['users'] as $result)
                    <a href="{{ $result->isAdmin() ? (auth()->user()->isSuperAdmin() ? route('admin.admins.edit', $result) : '#') : ($result->isPublisher() && auth()->user()->can('manage_publishers') ? route('admin.publishers.show', $result) : route('admin.users.edit', $result)) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                        <span class="text-truncate"><span class="fw-semibold">{{ $result->name }}</span> <span class="small text-muted-2">{{ $result->email }}</span></span>
                        <x-status-badge :status="$result->roleName()" />
                    </a>
                @endforeach
            </div></div></div>
        @endif
        @if ($term !== '' && $total === 0)
            <div class="col-12"><div class="card"><x-empty-state icon="bi-search" title="No matches" text="Try an order number, product name, email or transaction ID." /></div></div>
        @endif
    </div>
@endsection
