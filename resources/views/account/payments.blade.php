@extends('account.layout')

@section('account')
    <div class="panel-title"><div><h1>Payment history</h1><p>All payment details you have submitted.</p></div></div>

    <div class="card">
        @if ($payments->isEmpty())
            <x-empty-state icon="bi-credit-card" title="No payments yet" />
        @else
            <div class="table-responsive-md">
                <table class="table table-stack">
                    <thead><tr><th>Date</th><th>Order</th><th>Method</th><th>Transaction ID</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('M j, Y H:i') }}</td>
                                <td>@if ($payment->order)<a href="{{ route('orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a>@endif</td>
                                <td>{{ $payment->gateway?->name }}</td>
                                <td class="mono">{{ $payment->transaction_id }}</td>
                                <td class="fw-semibold">{{ money($payment->amount, $payment->currency) }}</td>
                                <td>
                                    <x-status-badge :status="$payment->status" />
                                    @if ($payment->rejection_reason)<div class="small text-danger">{{ $payment->rejection_reason }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())<div class="card-footer">{{ $payments->links() }}</div>@endif
        @endif
    </div>
@endsection
