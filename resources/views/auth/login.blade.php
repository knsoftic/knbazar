@extends('layouts.app')

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-1">Welcome back</h1>
                        <p class="text-muted-2 mb-4">Sign in to manage your orders and downloads.</p>
                        <form method="POST" action="{{ route('login') }}" novalidate>
                            @csrf
                            <x-input name="email" type="email" label="Email" required autofocus autocomplete="username" maxlength="255" />
                            <div class="mb-1 d-flex justify-content-between">
                                <label for="f_password" class="form-label">Password</label>
                                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                            </div>
                            <x-input name="password" type="password" required autocomplete="current-password" />
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                                <label class="form-check-label" for="remember">Keep me signed in</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg" data-loading="Signing in...">Sign in</button>
                        </form>
                        @if (setting('registration_enabled'))
                            <div class="divider my-4">New here?</div>
                            <div class="d-grid gap-2">
                                <a href="{{ route('register') }}" class="btn btn-light">Create a customer account</a>
                                @if (setting('publisher_registration_enabled'))
                                    <a href="{{ route('register.publisher') }}" class="btn btn-outline-primary">Register as a publisher</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
