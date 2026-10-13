@extends('layouts.admin')

@section('title', 'Admin dashboard')

@php
    $chart = fn (string $label, array $data, array $extra = []) => json_encode(['labels' => $charts['labels'], 'label' => $label, 'data' => $data] + $extra);
@endphp

@section('content')
    <div class="panel-title">
        <div><h1>Dashboard</h1><p>Marketplace overview &middot; charts show {{ strtolower($range->label) }}.</p></div>
        <form method="GET" class="d-flex gap-2">
            <select name="range" class="form-select form-select-sm" data-autosubmit aria-label="Chart range">
                @foreach (['last_30_days' => 'Last 30 days', 'this_week' => 'This week', 'this_month' => 'This month', 'last_month' => 'Last month'] as $key => $label)
                    <option value="{{ $key }}" @selected($range->key === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Total users" :value="number_format($stats['users'])" icon="bi-people" :href="auth()->user()->can('manage_users') ? route('admin.users.index') : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Publishers" :value="number_format($stats['publishers'])" icon="bi-shop" color="info" :href="auth()->user()->can('manage_publishers') ? route('admin.publishers.index') : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Total products" :value="number_format($stats['products'])" icon="bi-box-seam" color="dark" :href="auth()->user()->can('view_products') ? route('admin.products.index') : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Pending products" :value="number_format($stats['pending_products'])" icon="bi-hourglass-split" color="warning" :href="auth()->user()->can('view_products') ? route('admin.products.index', ['approval' => 'pending']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Free products" :value="number_format($stats['free_products'])" icon="bi-gift" color="success" :href="auth()->user()->can('view_products') ? route('admin.products.index', ['type' => 'free']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Paid products" :value="number_format($stats['paid_products'])" icon="bi-gem" color="pink" :href="auth()->user()->can('view_products') ? route('admin.products.index', ['type' => 'paid']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Total orders" :value="number_format($stats['orders'])" icon="bi-receipt" :href="auth()->user()->can('manage_orders') ? route('admin.orders.index') : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Pending payments" :value="number_format($stats['pending_payments'])" icon="bi-credit-card" color="warning" :href="auth()->user()->can('manage_payments') ? route('admin.payments.index', ['status' => 'under_review']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Approved payments" :value="number_format($stats['approved_payments'])" icon="bi-check2-circle" color="success" :href="auth()->user()->can('manage_payments') ? route('admin.payments.index', ['status' => 'approved']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Total revenue" :value="money($stats['revenue'])" icon="bi-cash-stack" color="success" :href="auth()->user()->can('view_reports') ? route('admin.reports.index', ['type' => 'revenue']) : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Total downloads" :value="number_format($stats['downloads'])" icon="bi-download" color="info" :href="auth()->user()->can('manage_downloads') ? route('admin.downloads.index') : null" /></div>
        <div class="col-6 col-md-4 col-xxl-3"><x-stat-card label="Customers" :value="number_format($stats['customers'])" icon="bi-person-badge" :href="auth()->user()->can('manage_users') ? route('admin.customers.index') : null" /></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6"><div class="card h-100"><div class="card-header">Orders by date</div><div class="card-body"><div class="chart-box"><canvas data-chart="{{ $chart('Orders', $charts['orders'], ['type' => 'bar']) }}" role="img" aria-label="Orders chart"></canvas></div></div></div></div>
        <div class="col-xl-6"><div class="card h-100"><div class="card-header">Revenue by date ({{ currency_code() }})</div><div class="card-body"><div class="chart-box"><canvas data-chart="{{ $chart('Revenue', $charts['revenue'], ['color' => '#16a34a']) }}" role="img" aria-label="Revenue chart"></canvas></div></div></div></div>
        <div class="col-xl-4"><div class="card h-100"><div class="card-header">Products added</div><div class="card-body"><div class="chart-box sm"><canvas data-chart="{{ $chart('Products', $charts['products'], ['type' => 'bar', 'color' => '#7c3aed']) }}" role="img" aria-label="Products chart"></canvas></div></div></div></div>
        <div class="col-xl-4"><div class="card h-100"><div class="card-header">Downloads</div><div class="card-body"><div class="chart-box sm"><canvas data-chart="{{ $chart('Downloads', $charts['downloads'], ['color' => '#0ea5e9']) }}" role="img" aria-label="Downloads chart"></canvas></div></div></div></div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">Publisher activity (products submitted)</div>
                <div class="card-body">
                    @if (empty($charts['publishers']['labels']))
                        <x-empty-state icon="bi-shop" title="No publisher activity in this period" />
                    @else
                        <div class="chart-box sm"><canvas data-chart="{{ json_encode(['labels' => $charts['publishers']['labels'], 'data' => $charts['publishers']['data'], 'label' => 'Products', 'type' => 'bar', 'horizontal' => true, 'color' => '#f59e0b']) }}" role="img" aria-label="Publisher activity chart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @can('view_products')
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">Products awaiting review <a href="{{ route('admin.products.index', ['approval' => 'pending']) }}" class="small">View all</a></div>
                    @if ($pendingProducts->isEmpty())
                        <x-empty-state icon="bi-check2-all" title="Nothing to review" />
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($pendingProducts as $product)
                                <a href="{{ route('admin.products.show', $product) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                                    <img src="{{ $product->thumbnailUrl() }}" class="thumb" alt="" style="width:52px;height:40px;object-fit:cover;border-radius:8px">
                                    <span class="min-w-0 flex-grow-1">
                                        <span class="d-block fw-semibold text-ink text-truncate">{{ $product->name }}</span>
                                        <span class="small text-muted-2">{{ $product->user?->name }} &middot; {{ $product->category?->name }} &middot; {{ $product->submitted_at?->diffForHumans() }}</span>
                                    </span>
                                    <x-price :product="$product" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endcan
        @can('manage_payments')
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">Payments awaiting verification <a href="{{ route('admin.payments.index', ['status' => 'under_review']) }}" class="small">View all</a></div>
                    @if ($pendingPayments->isEmpty())
                        <x-empty-state icon="bi-check2-all" title="No payments to verify" />
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($pendingPayments as $payment)
                                <a href="{{ route('admin.payments.show', $payment) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                                    <span class="min-w-0">
                                        <span class="d-block fw-semibold text-ink">{{ $payment->order?->order_number }} &middot; {{ $payment->order?->customer_name }}</span>
                                        <span class="small text-muted-2">{{ $payment->gateway?->name }} &middot; <span class="mono">{{ $payment->transaction_id }}</span></span>
                                    </span>
                                    <span class="fw-semibold text-nowrap">{{ money($payment->amount, $payment->currency) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endcan
        @can('manage_orders')
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">Recent orders <a href="{{ route('admin.orders.index') }}" class="small">View all</a></div>
                    @if ($recentOrders->isEmpty())
                        <x-empty-state icon="bi-receipt" title="No orders yet" />
                    @else
                        <div class="table-responsive-md">
                            <table class="table table-hover table-stack">
                                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
                                            <td>{{ $order->customer_name }}</td>
                                            <td class="fw-semibold">{{ money($order->total, $order->currency) }}</td>
                                            <td><x-status-badge :status="$order->payment_status" /></td>
                                            <td><x-status-badge :status="$order->status" /></td>
                                            <td>{{ $order->created_at->format('M j, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
@endpush
