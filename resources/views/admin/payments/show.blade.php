@extends('layouts.admin')

@section('title', 'Payment #'.$payment->id)

@php $order = $payment->order; $mismatch = $order && (float) $payment->amount !== (float) $order->total; @endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li><li class="breadcrumb-item active">#{{ $payment->id }}</li></ol></nav>
            <h1>Payment #{{ $payment->id }}</h1>
            <p class="d-flex gap-2 align-items-center mb-0"><x-status-badge :status="$payment->status" /> <span class="small text-muted-2">Submitted {{ $payment->created_at->format('M j, Y H:i') }}</span></p>
        </div>
        @if ($payment->isReviewable())
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('admin.payments.approve', $payment) }}" data-confirm="Approve this payment? The order will be marked paid/completed and downloads enabled." data-confirm-class="btn-success" data-confirm-button="Approve payment">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Approve payment</button>
                </form>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reasonModal" data-action="{{ route('admin.payments.reject', $payment) }}" data-title="Reject this payment" data-help="The customer sees this reason and can submit new payment details."><i class="bi bi-x-octagon"></i> Reject</button>
            </div>
        @endif
    </div>

    @if ($mismatch)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> The submitted amount ({{ money($payment->amount, $payment->currency) }}) differs from the order total ({{ money($order->total, $order->currency) }}).</div>
    @endif
    @if ($duplicates->isNotEmpty())
        <div class="alert alert-danger"><i class="bi bi-shield-exclamation"></i> This transaction ID was also used on: @foreach ($duplicates as $dup)<a href="{{ route('admin.payments.show', $dup) }}">#{{ $dup->id }} ({{ $dup->order?->order_number }}, {{ $dup->status->label() }})</a>@if (! $loop->last), @endif @endforeach</div>
    @endif
    @if ($payment->rejection_reason)
        <div class="alert alert-danger"><strong>Rejection reason:</strong> {{ $payment->rejection_reason }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-header">Transaction details</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Gateway</span><span>{{ $payment->gateway?->name }}</span></li>
                        <li><span>Transaction ID</span><span class="mono">{{ $payment->transaction_id }}</span></li>
                        <li><span>Amount</span><span class="fw-bold">{{ money($payment->amount, $payment->currency) }}</span></li>
                        <li><span>Sender name</span><span>{{ $payment->sender_name ?: '-' }}</span></li>
                        <li><span>Sender account</span><span class="mono">{{ $payment->sender_account ?: '-' }}</span></li>
                        <li><span>Customer notes</span><span>{{ $payment->notes ?: '-' }}</span></li>
                        <li><span>IP address</span><span class="mono small">{{ $payment->ip_address ?: '-' }}</span></li>
                        @if ($payment->reviewed_at)<li><span>Reviewed</span><span>{{ $payment->reviewed_at->format('M j, Y H:i') }} by {{ $payment->reviewer?->name }}</span></li>@endif
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Payment screenshot</div>
                <div class="card-body text-center">
                    @if ($payment->proof_path)
                        <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank"><img src="{{ route('admin.payments.proof', $payment) }}" alt="Payment proof" class="img-fluid rounded-3 border" style="max-height: 520px"></a>
                        <div class="small text-muted-2 mt-2">Stored privately. Click to open full size.</div>
                    @else
                        <span class="text-muted-2">No screenshot was submitted.</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">Order @if ($order && auth()->user()->can('manage_orders'))<a href="{{ route('admin.orders.show', $order) }}" class="small">Open order</a>@endif</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Order</span><span>{{ $order?->order_number }}</span></li>
                        <li><span>Order total</span><span>{{ money($order?->total, $order?->currency) }}</span></li>
                        <li><span>Order status</span><span><x-status-badge :status="$order?->status" /></span></li>
                        <li><span>Customer</span><span>{{ $order?->customer_name }}</span></li>
                        <li><span>Email</span><span>{{ $order?->customer_email }}</span></li>
                        <li><span>Phone</span><span>{{ $order?->customer_phone ?: '-' }}</span></li>
                    </ul>
                    <h3 class="h6 mt-3">Items</h3>
                    <ul class="list-unstyled small mb-0">
                        @foreach ($order?->items ?? [] as $item)
                            <li class="d-flex justify-content-between border-bottom py-1"><span>{{ $item->product_name }} &times; {{ $item->quantity }}</span><span>{{ money($item->total, $order->currency) }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header">History</div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach ($payment->statusHistories as $history)
                            <li>
                                <div class="fw-semibold small text-ink">{{ \App\Enums\PaymentRecordStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}</div>
                                @if ($history->note)<div class="small">{{ $history->note }}</div>@endif
                                <div class="when">{{ $history->created_at?->format('M j, Y H:i') }} @if ($history->user) &middot; {{ $history->user->name }} @endif</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
