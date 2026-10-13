@extends('account.layout')

@section('account')
    <div class="panel-title"><div><h1>My orders</h1><p>Track payment and order status.</p></div></div>

    <div class="card">
        @if ($orders->isEmpty())
            <x-empty-state icon="bi-receipt" title="No orders yet" text="When you buy a product, your order appears here.">
                <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">Browse products</a>
            </x-empty-state>
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                <td>{{ $order->created_at->format('M j, Y') }}</td>
                                <td><span class="d-inline-block text-truncate" style="max-width: 220px">{{ $order->items->pluck('product_name')->implode(', ') }}</span></td>
                                <td class="fw-semibold">{{ money($order->total, $order->currency) }}</td>
                                <td><x-status-badge :status="$order->payment_status" /></td>
                                <td><x-status-badge :status="$order->status" /></td>
                                <td class="text-end td-actions">
                                    @if ($order->canSubmitPayment())
                                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-sm btn-primary">Pay now</a>
                                    @else
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-light">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
        @endif
    </div>
@endsection
