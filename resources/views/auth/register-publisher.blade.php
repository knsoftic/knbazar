@extends('layouts.app')

@php
    $guidelines = \App\Models\Page::query()->active()->where('slug', 'publisher-guidelines')->first(['slug', 'title']);
    $countries = \App\Support\Countries::all();
    $user = auth()->user();
@endphp

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card wide">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-5">
                        <div class="cta-publisher h-100">
                            <div class="position-relative" style="z-index: 1">
                                <h1 class="h3 mb-3">Sell on {{ setting('site_name') }}</h1>
                                <ul class="list-unstyled d-grid gap-3 mb-0">
                                    <li><i class="bi bi-cloud-upload me-2"></i> Upload themes, plugins, templates, graphics, eBooks and more</li>
                                    <li><i class="bi bi-shield-check me-2"></i> Every product is reviewed before going live</li>
                                    <li><i class="bi bi-graph-up-arrow me-2"></i> Track sales and downloads in your dashboard</li>
                                    <li><i class="bi bi-gift me-2"></i> Offer free products to grow your audience</li>
                                </ul>
                                @if (setting('publisher_require_approval'))
                                    <p class="small mt-4 mb-0 opacity-75"><i class="bi bi-info-circle"></i> New publisher accounts are reviewed by our team before you can submit products.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <h2 class="h4 mb-1">{{ $upgrading ? 'Upgrade to a publisher account' : 'Create a publisher account' }}</h2>
                                <p class="text-muted-2 mb-4">{{ $upgrading ? 'Your existing orders and downloads stay in your account.' : 'Already have a customer account? Sign in first to upgrade it.' }}</p>
                                <form method="POST" action="{{ route('register.publisher') }}" novalidate>
                                    @csrf
                                    @unless ($upgrading)
                                        <div class="row">
                                            <div class="col-md-6"><x-input name="name" label="Your full name" required maxlength="100" autocomplete="name" /></div>
                                            <div class="col-md-6"><x-input name="email" type="email" label="Email" required maxlength="150" autocomplete="email" /></div>
                                            <div class="col-md-6"><x-input name="password" type="password" label="Password" required autocomplete="new-password" /></div>
                                            <div class="col-md-6"><x-input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" /></div>
                                        </div>
                                    @endunless
                                    <div class="row">
                                        <div class="col-md-6"><x-input name="display_name" label="Store / brand name" required maxlength="100" /></div>
                                        <div class="col-md-6"><x-input name="username" label="Username" prepend="@" required maxlength="30" :value="$user?->username" help="Your store URL: /publisher/username" pattern="[a-z0-9][a-z0-9_\-]*" /></div>
                                        <div class="col-md-6"><x-input name="phone" label="Phone / WhatsApp" required maxlength="40" :value="$user?->phone" autocomplete="tel" /></div>
                                        <div class="col-md-6"><x-select name="country" label="Country" :options="array_combine($countries, $countries)" :value="$user?->country ?: 'Pakistan'" required placeholder="Select country" /></div>
                                    </div>
                                    <x-input name="website" type="url" label="Website / portfolio (optional)" maxlength="255" placeholder="https://" />
                                    <x-textarea name="bio" label="About you (optional)" rows="3" maxlength="2000" />
                                    <div class="form-check mb-3">
                                        <input class="form-check-input @error('guidelines') is-invalid @enderror" type="checkbox" name="guidelines" value="1" id="guidelines" @checked(old('guidelines')) required>
                                        <label class="form-check-label small" for="guidelines">I have read and accept the @if ($guidelines)<a href="{{ $guidelines->url() }}" target="_blank">{{ $guidelines->title }}</a>@else publisher guidelines @endif</label>
                                        @error('guidelines')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100" data-loading="Submitting...">{{ $upgrading ? 'Become a publisher' : 'Create publisher account' }}</button>
                                </form>
                                @guest<p class="text-center small mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Sign in</a></p>@endguest
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
