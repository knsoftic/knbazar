@extends('account.layout')

@php $selected = (int) old('payment_gateway_id', $order->payment_gateway_id ?? $gateways->first()?->id); @endphp

@section('account')
    <div class="panel-title">
        <div>
            <h1>Complete your payment</h1>
            <p>Order <a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a> &middot; Total <strong>{{ money($order->total, $order->currency) }}</strong></p>
        </div>
    </div>

    @if ($lastPayment && $lastPayment->rejection_reason)
        <div class="alert alert-danger"><strong>Previous payment rejected:</strong> {{ $lastPayment->rejection_reason }}</div>
    @endif

    @if ($gateways->isEmpty())
        <div class="alert alert-warning">No payment methods are currently available. Please <a href="{{ route('contact.show') }}">contact us</a>.</div>
    @else
        <form method="POST" action="{{ route('orders.pay.store', $order) }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header"><span class="badge text-bg-primary rounded-pill me-1">1</span> Send the payment</div>
                        <div class="card-body">
                            @error('payment_gateway_id')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                            <div class="d-grid gap-2 mb-3">
                                @foreach ($gateways as $gateway)
                                    <label class="gateway-option">
                                        <span class="d-flex align-items-center">
                                            <input type="radio" class="form-check-input mt-0" name="payment_gateway_id" value="{{ $gateway->id }}" @checked($selected === $gateway->id)>
                                            <span class="fw-semibold text-ink">{{ $gateway->name }}</span>
                                            @if ($gateway->requires_proof)<span class="badge badge-soft-secondary ms-auto">Screenshot required</span>@endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @foreach ($gateways as $gateway)
                                <div id="gateway-info-{{ $gateway->id }}" class="border rounded-3 p-3 {{ $selected === $gateway->id ? '' : 'd-none' }}">
                                    @include('partials.gateway-details', ['gateway' => $gateway, 'amount' => $order->total])
                                    <div class="account-detail mt-2"><span class="small text-muted-2">Payment reference</span><span class="d-flex gap-2 align-items-center"><span class="value">{{ $order->order_number }}</span><button type="button" class="btn btn-sm btn-light btn-icon" data-copy="{{ $order->order_number }}" aria-label="Copy order number"><i class="bi bi-clipboard"></i></button></span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header"><span class="badge text-bg-primary rounded-pill me-1">2</span> Submit transaction details</div>
                        <div class="card-body">
                            <x-input name="transaction_id" label="Transaction ID / reference" required maxlength="150" placeholder="e.g. 0123456789" />
                            <div class="row">
                                <div class="col-sm-6"><x-input name="sender_name" label="Sender name" :value="$order->customer_name" maxlength="150" /></div>
                                <div class="col-sm-6"><x-input name="sender_account" label="Sender account / number" maxlength="150" /></div>
                            </div>
                            <x-input name="amount" type="number" step="0.01" min="0.01" label="Amount paid" :value="number_format((float) $order->total, 2, '.', '')" required :append="$order->currency" />
                            <div class="mb-3">
                                <label for="proof" class="form-label">Payment screenshot</label>
                                <input type="file" name="proof" id="proof" class="form-control @error('proof') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#proofPreview" data-max-mb="{{ round(\App\Support\UploadRules::imageMaxKb() / 1024, 1) }}">
                                @error('proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ strtoupper(\App\Support\UploadRules::imageTypes()) }}, max {{ \App\Support\UploadRules::imageMaxKb() }} KB. Required for some payment methods.</div>
                                <img id="proofPreview" class="upload-preview mt-2 d-none" alt="Screenshot preview">
                            </div>
                            <x-textarea name="notes" label="Notes (optional)" rows="2" maxlength="1000" />
                            <button type="submit" class="btn btn-primary btn-lg w-100" data-loading="Submitting..."><i class="bi bi-send-check"></i> Submit payment for verification</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection
