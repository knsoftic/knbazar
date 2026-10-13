@extends('layouts.app')

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5 text-center">
                        <i class="bi bi-envelope-check display-5 text-primary"></i>
                        <h1 class="h4 mt-3">Verify your email address</h1>
                        <p class="text-muted-2">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to continue. Didn't get it?</p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary" data-loading="Sending...">Resend verification email</button>
                        </form>
                        <a href="{{ route('account.profile') }}" class="btn btn-link mt-2">Wrong email? Update it in your profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
