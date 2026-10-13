@extends('layouts.app')

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li><li class="breadcrumb-item active" aria-current="page">Checkout</li></ol></nav>
            <h1 class="mb-0">Checkout</h1>
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        @if ($owned->isNotEmpty())
            <div class="alert alert-warning">
                You already own: <strong>{{ $owned->map(fn ($l) => $l['product']->name)->implode(', ') }}</strong>. Remove {{ $owned->count() > 1 ? 'them' : 'it' }} from your <a href="{{ route('cart.index') }}">cart</a> to continue.
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" novalidate>
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-person-vcard me-1"></i> Your details</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6"><x-input name="customer_name" label="Full name" :value="$user->name" required maxlength="100" autocomplete="name" /></div>
                                <div class="col-md-6"><x-input name="customer_email" type="email" label="Email" :value="$user->email" required maxlength="150" autocomplete="email" help="Order updates are sent here." /></div>
                                <div class="col-md-6"><x-input name="customer_phone" label="WhatsApp / phone" :value="$user->phone" required maxlength="40" autocomplete="tel" /></div>
                                <div class="col-md-6">
                                    <x-select name="customer_country" label="Country" :options="array_combine($countries, $countries)" :value="$user->country ?: 'Pakistan'" required placeholder="Select country" />
                                </div>
                            </div>
                            <x-textarea name="customer_address" label="Address" :value="$user->address" rows="2" maxlength="500" :required="(bool) setting('checkout_require_address')" />
                            <x-textarea name="notes" label="Order notes (optional)" rows="2" maxlength="1000" placeholder="Anything we should know?" />
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><i class="bi bi-wallet2 me-1"></i> Payment method</div>
                        <div class="card-body">
                            @if ($gateways->isEmpty())
                                <div class="alert alert-warning mb-0">No payment methods are available right now. Please contact us to complete your order.</div>
                            @else
                                @error('payment_gateway_id')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                                <div class="d-grid gap-2">
                                    @foreach ($gateways as $gateway)
                                        <label class="gateway-option">
                                            <span class="d-flex align-items-center">
                                                <input type="radio" class="form-check-input mt-0" name="payment_gateway_id" value="{{ $gateway->id }}" @checked((int) old('payment_gateway_id', $loop->first ? $gateway->id : 0) === $gateway->id)>
                                                @if ($gateway->logoUrl())<img src="{{ $gateway->logoUrl() }}" alt="" class="gateway-logo me-2">@endif
                                                <span>
                                                    <span class="fw-semibold text-ink d-block">{{ $gateway->name }}</span>
                                                    @if ($gateway->description)<span class="small text-muted-2">{{ $gateway->description }}</span>@endif
                                                </span>
                                            </span>
                                        </label>
                                        <div id="gateway-info-{{ $gateway->id }}" class="border rounded-3 p-3 bg-light-subtle d-none">
                                            @include('partials.gateway-details', ['gateway' => $gateway, 'amount' => $totals['total']])
                                            <div class="small text-muted-2 mt-2"><i class="bi bi-info-circle"></i> After placing the order you'll submit your transaction ID{{ $gateway->requires_proof ? ' and payment screenshot' : '' }}.</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card buy-box">
                        <div class="card-header">Order summary</div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-3">
                                @foreach ($lines as $line)
                                    <li class="d-flex gap-3 align-items-center py-2 border-bottom">
                                        <img src="{{ $line['product']->thumbnailUrl() }}" class="thumb" alt="" loading="lazy">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-ink text-truncate">{{ $line['product']->name }}</div>
                                            <div class="small text-muted-2">Qty {{ $line['quantity'] }} &times; {{ money($line['price']) }}
                                                @if ($line['discount'] > 0)<span class="text-decoration-line-through ms-1">{{ money($line['unit_price']) }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="fw-semibold text-nowrap">{{ money($line['total']) }}</div>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="summary-line"><span>Subtotal</span><span>{{ money($totals['subtotal']) }}</span></div>
                            @if ($totals['discount'] > 0)<div class="summary-line text-success"><span>Discount</span><span>-{{ money($totals['discount']) }}</span></div>@endif
                            <div class="summary-line summary-total"><span>Total</span><span>{{ money($totals['total']) }}</span></div>

                            <div class="form-check mt-3">
                                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" value="1" id="terms" @checked(old('terms')) required>
                                <label class="form-check-label small" for="terms">
                                    I agree to the
                                    @if ($termsPage)<a href="{{ $termsPage->url() }}" target="_blank">{{ $termsPage->title }}</a>@else terms and conditions @endif
                                    and understand digital products are delivered as downloads.
                                </label>
                                @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" data-loading="Placing order..." @disabled($gateways->isEmpty() || $owned->isNotEmpty())>
                                <i class="bi bi-lock"></i> Place order
                            </button>
                            <p class="small text-muted-2 text-center mt-2 mb-0">Your order status will be <strong>Awaiting payment</strong> until you submit your payment details.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
