@extends('layouts.publisher')

@section('title', 'Orders')

@section('content')
    <div class="panel-title"><div><h1>Orders</h1><p>Orders that include your products.</p></div></div>

    <div class="card">
        @if ($items->isEmpty())
            <x-empty-state icon="bi-receipt" title="No orders yet" text="When customers buy your products, they will appear here." />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Order</th><th>Product</th><th>Customer</th><th>Qty</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $item->order?->order_number }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->order?->customer_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="fw-semibold">{{ money($item->total, $item->order?->currency) }}</td>
                                <td><x-status-badge :status="$item->order?->payment_status" /></td>
                                <td><x-status-badge :status="$item->order?->status" /></td>
                                <td>{{ $item->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
        @endif
    </div>
@endsection
