@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)

@php use App\Enums\OrderStatus; $latest = $order->payments->first(); @endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Orders</a></li><li class="breadcrumb-item active">{{ $order->order_number }}</li></ol></nav>
            <h1>Order {{ $order->order_number }}</h1>
            <p class="d-flex gap-2 align-items-center flex-wrap mb-0"><x-status-badge :status="$order->status" /> <x-status-badge :status="$order->payment_status" /> <span class="small text-muted-2">Placed {{ $order->created_at->format('M j, Y H:i') }}</span></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($latest && $latest->isReviewable() && auth()->user()->can('manage_payments'))
                <a href="{{ route('admin.payments.show', $latest) }}" class="btn btn-warning"><i class="bi bi-credit-card"></i> Verify payment</a>
            @endif
            @if ($order->status !== OrderStatus::Completed && ! $order->isClosed())
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-confirm="{{ $order->isPaid() ? 'Mark this order as completed?' : 'Payment has not been verified. Mark completed anyway? This confirms the payment manually and grants downloads.' }}" data-confirm-class="btn-success" data-confirm-button="Mark completed">
                    @csrf
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Mark completed</button>
                </form>
            @endif
            @if (! $order->isClosed())
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-confirm="Cancel this order? Download access will be revoked.">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Cancel order</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">Purchased products</div>
                <div class="table-responsive-md">
                    <table class="table table-stack">
                        <thead><tr><th>Product</th><th>Publisher</th><th>Unit</th><th>Paid</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        @if ($item->product && ! $item->product->trashed())
                                            <a href="{{ route('admin.products.show', $item->product) }}" class="fw-semibold">{{ $item->product_name }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $item->product_name }}</span> <span class="badge badge-soft-secondary">removed</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->publisher?->name ?? '-' }}</td>
                                    <td>{{ money($item->unit_price, $order->currency) }}</td>
                                    <td>{{ money($item->sale_price, $order->currency) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->total, $order->currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="ms-auto" style="max-width: 320px">
                        <div class="summary-line"><span>Subtotal</span><span>{{ money($order->subtotal, $order->currency) }}</span></div>
                        <div class="summary-line text-success"><span>Discount</span><span>-{{ money($order->discount_total, $order->currency) }}</span></div>
                        <div class="summary-line summary-total"><span>Total</span><span>{{ money($order->total, $order->currency) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Payment submissions</div>
                @if ($order->payments->isEmpty())
                    <x-empty-state icon="bi-credit-card" title="No payment submitted yet" />
                @else
                    <div class="table-responsive-md">
                        <table class="table table-stack">
                            <thead><tr><th>ID</th><th>Gateway</th><th>Transaction ID</th><th>Sender</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr>
                                        <td>#{{ $payment->id }}</td>
                                        <td>{{ $payment->gateway?->name }}</td>
                                        <td class="mono">{{ $payment->transaction_id }}</td>
                                        <td>{{ $payment->sender_name }}<div class="small text-muted-2">{{ $payment->sender_account }}</div></td>
                                        <td class="fw-semibold">{{ money($payment->amount, $payment->currency) }} @if ((float) $payment->amount !== (float) $order->total)<i class="bi bi-exclamation-triangle text-warning" title="Differs from order total"></i>@endif</td>
                                        <td><x-status-badge :status="$payment->status" />@if ($payment->rejection_reason)<div class="small text-danger">{{ $payment->rejection_reason }}</div>@endif</td>
                                        <td class="text-nowrap">{{ $payment->created_at->format('M j, H:i') }}</td>
                                        <td class="text-end td-actions"><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-light">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">Download access</div>
                @if ($order->downloadPermissions->isEmpty())
                    <div class="card-body small text-muted-2">No download permissions yet. They are granted when the payment is approved or the order is completed.</div>
                @else
                    <div class="table-responsive-md">
                        <table class="table table-stack">
                            <thead><tr><th>Product</th><th>Downloads</th><th>Expires</th><th>State</th></tr></thead>
                            <tbody>
                                @foreach ($order->downloadPermissions as $permission)
                                    <tr>
                                        <td>{{ $permission->product?->name }}</td>
                                        <td>{{ $permission->downloads_count }}{{ $permission->download_limit !== null ? ' / '.$permission->download_limit : '' }}</td>
                                        <td>{{ $permission->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                                        <td>{!! $permission->isUsable() ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-danger">'.($permission->isRevoked() ? 'Revoked' : ($permission->isExpired() ? 'Expired' : 'Limit reached')).'</span>' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Customer</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Name</span><span>{{ $order->customer_name }}</span></li>
                        <li><span>Email</span><span><a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a></span></li>
                        <li><span>Phone</span><span>{{ $order->customer_phone ?: '-' }}</span></li>
                        <li><span>Country</span><span>{{ $order->customer_country ?: '-' }}</span></li>
                        @if ($order->customer_address)<li><span>Address</span><span>{{ $order->customer_address }}</span></li>@endif
                        <li><span>Account</span><span>@if ($order->user){{ $order->user->email }}@if (auth()->user()->can('manage_users') && ! $order->user->isAdmin()) <a href="{{ route('admin.users.edit', $order->user) }}"><i class="bi bi-box-arrow-up-right"></i></a>@endif @else Deleted @endif</span></li>
                        <li><span>IP</span><span class="mono small">{{ $order->ip_address ?: '-' }}</span></li>
                    </ul>
                    @if ($order->notes)<div class="mt-3 small"><strong>Customer notes:</strong><br>{{ $order->notes }}</div>@endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Update status</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-confirm="Change the order status?" data-confirm-class="btn-primary" data-confirm-button="Update">
                        @csrf
                        <x-select name="status" label="Status" :options="\App\Enums\OrderStatus::options()" :value="$order->status" />
                        <x-textarea name="note" label="Note (optional)" rows="2" maxlength="1000" />
                        <button type="submit" class="btn btn-primary w-100">Update status</button>
                        <div class="form-text">Completed grants downloads (and confirms payment); Cancelled/Refunded revoke them.</div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Internal notes</div>
                <div class="card-body">
                    @if ($order->admin_notes)<pre class="small bg-light p-2 rounded-3" style="white-space: pre-wrap">{{ $order->admin_notes }}</pre>@endif
                    <form method="POST" action="{{ route('admin.orders.notes', $order) }}">
                        @csrf
                        <x-textarea name="note" rows="2" maxlength="2000" placeholder="Only staff can see this note" aria-label="Internal note" />
                        <button type="submit" class="btn btn-light w-100">Add note</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">History</div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach ($order->statusHistories as $history)
                            <li>
                                <div class="fw-semibold small text-ink">{{ OrderStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}</div>
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
