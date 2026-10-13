@extends('account.layout')

@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    $lastPayment = $order->payments->first();
    $steps = [
        ['Order placed', true],
        ['Payment submitted', in_array($order->payment_status, [PaymentStatus::Submitted, PaymentStatus::Approved], true)],
        ['Verified', $order->isPaid()],
        ['Download available', $order->grantsDownloads()],
    ];
    $currentStep = collect($steps)->search(fn ($s) => ! $s[1]);
@endphp

@section('account')
    <div class="panel-title">
        <div>
            <h1>Order {{ $order->order_number }}</h1>
            <p>Placed {{ $order->created_at->format('M j, Y \a\t g:i A') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <x-status-badge :status="$order->status" class="fs-6" />
            @if ($order->canSubmitPayment())
                <a href="{{ route('orders.pay', $order) }}" class="btn btn-primary"><i class="bi bi-credit-card"></i> {{ $order->payment_status === PaymentStatus::Rejected ? 'Resubmit payment' : 'Submit payment' }}</a>
            @endif
        </div>
    </div>

    @unless ($order->isClosed())
        <div class="card mb-4">
            <div class="card-body">
                <div class="steps">
                    @foreach ($steps as $i => [$label, $done])
                        <div class="step {{ $done ? 'done' : ($i === $currentStep ? 'current' : '') }}">{{ $label }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    @endunless

    @if ($order->payment_status === PaymentStatus::Rejected && $lastPayment?->rejection_reason)
        <div class="alert alert-danger">
            <strong><i class="bi bi-x-octagon"></i> Your last payment was rejected.</strong>
            <div>Reason: {{ $lastPayment->rejection_reason }}</div>
            <a href="{{ route('orders.pay', $order) }}" class="btn btn-sm btn-danger mt-2">Submit payment again</a>
        </div>
    @elseif ($order->status === OrderStatus::PaymentSubmitted)
        <div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Your payment is <strong>under review</strong>. You'll be notified as soon as it is verified.</div>
    @elseif ($order->grantsDownloads())
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-check-circle"></i> Payment verified - your downloads are ready.</span>
            <a href="{{ route('downloads.index') }}" class="btn btn-sm btn-success">Go to downloads</a>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">Items</div>
                <div class="table-responsive-md">
                    <table class="table table-stack">
                        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            @if ($item->product)<img src="{{ $item->product->thumbnailUrl() }}" class="thumb" alt="" loading="lazy">@endif
                                            @if ($item->product && ! $item->product->trashed())
                                                <a href="{{ route('products.show', $item->product->slug) }}" class="fw-semibold">{{ $item->product_name }}</a>
                                            @else
                                                <span class="fw-semibold">{{ $item->product_name }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ money($item->sale_price, $order->currency) }}
                                        @if ((float) $item->discount > 0)<div class="small text-muted-2 text-decoration-line-through">{{ money($item->unit_price, $order->currency) }}</div>@endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->total, $order->currency) }}</td>
                                    <td class="text-end td-actions">
                                        @if ($order->grantsDownloads() && $item->product && ! $item->product->trashed())
                                            <a href="{{ route('downloads.download', $item->product->slug) }}" class="btn btn-sm btn-success" rel="nofollow"><i class="bi bi-download"></i> Download</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="ms-auto" style="max-width: 320px">
                        <div class="summary-line"><span>Subtotal</span><span>{{ money($order->subtotal, $order->currency) }}</span></div>
                        @if ((float) $order->discount_total > 0)<div class="summary-line text-success"><span>Discount</span><span>-{{ money($order->discount_total, $order->currency) }}</span></div>@endif
                        <div class="summary-line summary-total"><span>Total</span><span>{{ money($order->total, $order->currency) }}</span></div>
                    </div>
                </div>
            </div>

            @if ($order->payments->isNotEmpty())
                <div class="card">
                    <div class="card-header">Payment submissions</div>
                    <div class="table-responsive-md">
                        <table class="table table-stack">
                            <thead><tr><th>Date</th><th>Method</th><th>Transaction ID</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at->format('M j, Y H:i') }}</td>
                                        <td>{{ $payment->gateway?->name }}</td>
                                        <td class="mono">{{ $payment->transaction_id }}</td>
                                        <td>{{ money($payment->amount, $payment->currency) }}</td>
                                        <td>
                                            <x-status-badge :status="$payment->status" />
                                            @if ($payment->rejection_reason)<div class="small text-danger mt-1">{{ $payment->rejection_reason }}</div>@endif
                                        </td>
                                        <td class="text-end td-actions">
                                            @if ($payment->proof_path)
                                                <a href="{{ route('orders.payments.proof', [$order, $payment]) }}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-image"></i> Screenshot</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Details</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Payment</span><span><x-status-badge :status="$order->payment_status" /></span></li>
                        <li><span>Method</span><span>{{ $order->gateway?->name ?? '-' }}</span></li>
                        @if ($order->transaction_id)<li><span>Transaction</span><span class="mono">{{ $order->transaction_id }}</span></li>@endif
                        <li><span>Name</span><span>{{ $order->customer_name }}</span></li>
                        <li><span>Email</span><span>{{ $order->customer_email }}</span></li>
                        @if ($order->customer_phone)<li><span>Phone</span><span>{{ $order->customer_phone }}</span></li>@endif
                        @if ($order->customer_country)<li><span>Country</span><span>{{ $order->customer_country }}</span></li>@endif
                        @if ($order->paid_at)<li><span>Paid</span><span>{{ $order->paid_at->format('M j, Y') }}</span></li>@endif
                    </ul>
                    @if ($order->notes)<div class="small mt-3"><strong>Your notes:</strong> {{ $order->notes }}</div>@endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">History</div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach ($order->statusHistories as $history)
                            <li>
                                <div class="fw-semibold small text-ink">{{ OrderStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}</div>
                                @if ($history->note)<div class="small">{{ $history->note }}</div>@endif
                                <div class="when">{{ $history->created_at?->format('M j, Y H:i') }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if ($order->canBeCancelledByCustomer())
                <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel order {{ $order->order_number }}? This cannot be undone.">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Cancel order</button>
                </form>
            @endif
        </div>
    </div>
@endsection
