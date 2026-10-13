@extends('layouts.app')

@php $terms = \App\Models\Page::query()->active()->where('slug', setting('checkout_terms_page'))->first(['slug', 'title']); @endphp

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-1">Create your account</h1>
                        <p class="text-muted-2 mb-4">Buy products, download freebies and track your orders.</p>
                        <form method="POST" action="{{ route('register') }}" novalidate>
                            @csrf
                            <x-input name="name" label="Full name" required autofocus autocomplete="name" maxlength="100" />
                            <x-input name="email" type="email" label="Email" required autocomplete="email" maxlength="150" />
                            <x-input name="phone" label="Phone / WhatsApp (optional)" autocomplete="tel" maxlength="40" />
                            <x-input name="password" type="password" label="Password" required autocomplete="new-password" help="At least 8 characters with letters and numbers." />
                            <x-input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" value="1" id="terms" @checked(old('terms')) required>
                                <label class="form-check-label small" for="terms">I agree to the @if ($terms)<a href="{{ $terms->url() }}" target="_blank">{{ $terms->title }}</a>@else terms and conditions @endif</label>
                                @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg" data-loading="Creating account...">Create account</button>
                        </form>
                        <p class="text-center small mt-4 mb-0">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
                        @if (setting('publisher_registration_enabled'))
                            <p class="text-center small mt-2 mb-0">Want to sell? <a href="{{ route('register.publisher') }}">Register as a publisher</a></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
