@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div class="panel-title"><div><h1>Orders</h1><p>All marketplace orders.</p></div></div>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small" for="q">Search</label><input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="Order #, customer, transaction"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="status">Order status</label>
                <select name="status" id="status" class="form-select"><option value="">Any</option>@foreach (\App\Enums\OrderStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="payment">Payment</label>
                <select name="payment" id="payment" class="form-select"><option value="">Any</option>@foreach (\App\Enums\PaymentStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('payment') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="from">From</label><input type="date" name="from" id="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="to">To</label><input type="date" name="to" id="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit" aria-label="Filter"><i class="bi bi-funnel"></i></button></div>
        </div>
    </form>

    <div class="card">
        @if ($orders->isEmpty())
            <x-empty-state icon="bi-receipt" title="No orders found" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Gateway</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
                                <td><div class="text-ink">{{ $order->customer_name }}</div><div class="small text-muted-2">{{ $order->customer_email }}</div></td>
                                <td>{{ $order->items_count }}</td>
                                <td class="fw-semibold text-nowrap">{{ money($order->total, $order->currency) }}</td>
                                <td>{{ $order->gateway?->name ?? '-' }}</td>
                                <td><x-status-badge :status="$order->payment_status" /></td>
                                <td><x-status-badge :status="$order->status" /></td>
                                <td class="text-nowrap">{{ $order->created_at->format('M j, Y H:i') }}</td>
                                <td class="text-end td-actions"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-light">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
        @endif
    </div>
@endsection
