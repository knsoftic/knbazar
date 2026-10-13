@extends('layouts.admin')

@section('title', $user->exists ? 'Edit admin' : 'Add admin')

@php
    $selected = old('permissions', $selected);
    $groups = collect(config('permissions'))->groupBy(fn ($p) => $p['group'], preserveKeys: true);
@endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.admins.index') }}">Admins</a></li><li class="breadcrumb-item active">{{ $user->exists ? $user->email : 'New' }}</li></ol></nav>
            <h1>{{ $user->exists ? 'Edit admin' : 'Add admin' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $user->exists ? route('admin.admins.update', $user) : route('admin.admins.store') }}" novalidate>
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-header">Account</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="name" label="Full name" :value="$user->name" required maxlength="100" /></div>
                            <div class="col-md-6"><x-input name="username" label="Username" :value="$user->username" required maxlength="30" prepend="@" /></div>
                            <div class="col-md-6"><x-input name="email" type="email" label="Email" :value="$user->email" required maxlength="150" /></div>
                            <div class="col-md-6"><x-input name="phone" label="Phone" :value="$user->phone" maxlength="40" /></div>
                            <div class="col-md-6"><x-input name="password" type="password" :label="$user->exists ? 'New password (optional)' : 'Password'" :required="! $user->exists" autocomplete="new-password" /></div>
                            <div class="col-md-6"><x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" /></div>
                            <div class="col-md-6"><x-select name="role" label="Role" :options="['admin' => 'Admin', 'super_admin' => 'Super Admin']" :value="$user->role?->slug ?? 'admin'" required /></div>
                            <div class="col-md-6"><x-select name="status" label="Status" :options="\App\Enums\UserStatus::options()" :value="$user->status ?? 'active'" required /></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-header">Permissions <span class="small text-muted-2 fw-normal">(ignored for Super Admins)</span></div>
                    <div class="card-body">
                        @error('permissions')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        @foreach ($groups as $group => $permissions)
                            <div class="mb-3">
                                <div class="small text-uppercase text-muted-2 fw-semibold mb-1">{{ $group }}</div>
                                @foreach ($permissions as $slug => $permission)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $slug }}" id="perm_{{ $slug }}" @checked(in_array($slug, $selected, true))>
                                        <label class="form-check-label" for="perm_{{ $slug }}">{{ $permission['label'] }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save admin</button>
            </div>
        </div>
    </form>
@endsection
