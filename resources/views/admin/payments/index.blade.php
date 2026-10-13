@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
    <div class="panel-title"><div><h1>Payment submissions</h1><p>Verify manual payments. Submissions under review are listed first.</p></div></div>

    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        <li class="nav-item"><a class="nav-link {{ ! $status ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">All</a></li>
        @foreach (\App\Enums\PaymentRecordStatus::options() as $value => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $value ? 'active' : '' }}" href="{{ route('admin.payments.index', ['status' => $value]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="col-md-6"><label class="form-label small" for="q">Search</label><input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="Transaction ID, sender, order #, customer"></div>
            <div class="col-md-4"><label class="form-label small" for="gateway">Gateway</label>
                <select name="gateway" id="gateway" class="form-select"><option value="">All gateways</option>@foreach ($gateways as $g)<option value="{{ $g->id }}" @selected((string) request('gateway') === (string) $g->id)>{{ $g->name }}</option>@endforeach</select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button></div>
        </div>
    </form>

    <div class="card">
        @if ($payments->isEmpty())
            <x-empty-state icon="bi-credit-card" title="No payments found" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Payment</th><th>Order</th><th>Customer</th><th>Gateway</th><th>Amount</th><th>Transaction ID</th><th>Screenshot</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="fw-semibold">#{{ $payment->id }}</td>
                                <td>@if ($payment->order)<a href="{{ route('admin.orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a>@endif</td>
                                <td>{{ $payment->order?->customer_name }}</td>
                                <td>{{ $payment->gateway?->name }}</td>
                                <td class="fw-semibold text-nowrap">
                                    {{ money($payment->amount, $payment->currency) }}
                                    @if ($payment->order && (float) $payment->amount !== (float) $payment->order->total)<i class="bi bi-exclamation-triangle text-warning" title="Order total is {{ money($payment->order->total, $payment->order->currency) }}"></i>@endif
                                </td>
                                <td class="mono">{{ $payment->transaction_id }}</td>
                                <td>@if ($payment->proof_path)<a href="{{ route('admin.payments.proof', $payment) }}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-image"></i> View</a>@else <span class="text-muted-2 small">None</span> @endif</td>
                                <td><x-status-badge :status="$payment->status" /></td>
                                <td class="text-nowrap">{{ $payment->created_at->format('M j, Y H:i') }}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                                        @if ($payment->isReviewable())
                                            <form method="POST" action="{{ route('admin.payments.approve', $payment) }}" data-confirm="Approve payment #{{ $payment->id }} of {{ money($payment->amount, $payment->currency) }}? The order will be completed and downloads enabled." data-confirm-class="btn-success" data-confirm-button="Approve">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check2"></i></button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Reject" data-bs-toggle="modal" data-bs-target="#reasonModal" data-action="{{ route('admin.payments.reject', $payment) }}" data-title="Reject payment #{{ $payment->id }}" data-help="The customer sees this reason and can resubmit."><i class="bi bi-x-lg"></i></button>
                                        @endif
                                    </div>
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
