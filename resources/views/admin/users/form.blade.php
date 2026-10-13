@extends('layouts.admin')

@section('title', $user->exists ? 'Edit user' : 'Add user')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li><li class="breadcrumb-item active">{{ $user->exists ? $user->email : 'New' }}</li></ol></nav>
            <h1>{{ $user->exists ? 'Edit user' : 'Add user' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" novalidate>
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="name" label="Full name" :value="$user->name" required maxlength="100" /></div>
                            <div class="col-md-6"><x-input name="username" label="Username" :value="$user->username" required maxlength="30" prepend="@" /></div>
                            <div class="col-md-6"><x-input name="email" type="email" label="Email" :value="$user->email" required maxlength="150" /></div>
                            <div class="col-md-6"><x-input name="phone" label="Phone" :value="$user->phone" maxlength="40" /></div>
                            <div class="col-md-6"><x-select name="country" label="Country" :options="array_combine($countries, $countries)" :value="$user->country" placeholder="Select country" /></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6"><x-input name="password" type="password" :label="$user->exists ? 'New password (leave empty to keep)' : 'Password'" :required="! $user->exists" autocomplete="new-password" /></div>
                            <div class="col-md-6"><x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" /></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <x-select name="role" label="Role" :options="['customer' => 'Customer', 'publisher' => 'Publisher']" :value="$user->role?->slug ?? 'customer'" required help="Converting to publisher creates an approved publisher profile." />
                        <x-select name="status" label="Account status" :options="\App\Enums\UserStatus::options()" :value="$user->status ?? 'active'" required help="Suspended users are signed out and cannot log in." />
                        <x-toggle name="email_verified" label="Email verified" :checked="$user->exists ? $user->email_verified_at !== null : true" />
                        <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save user</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
