@extends('layouts.app')

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-1">Forgot your password?</h1>
                        <p class="text-muted-2 mb-4">Enter your email and we'll send you a link to reset it.</p>
                        <form method="POST" action="{{ route('password.email') }}" novalidate>
                            @csrf
                            <x-input name="email" type="email" label="Email" required autofocus autocomplete="email" maxlength="150" />
                            <button type="submit" class="btn btn-primary w-100" data-loading="Sending...">Email reset link</button>
                        </form>
                        <p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
