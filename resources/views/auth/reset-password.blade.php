@extends('layouts.app')

@section('content')
    <div class="auth-wrap">
        <div class="container">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-4">Choose a new password</h1>
                        <form method="POST" action="{{ route('password.store') }}" novalidate>
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">
                            <x-input name="email" type="email" label="Email" :value="$request->email" required autocomplete="username" />
                            <x-input name="password" type="password" label="New password" required autocomplete="new-password" help="At least 8 characters with letters and numbers." />
                            <x-input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                            <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Reset password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
