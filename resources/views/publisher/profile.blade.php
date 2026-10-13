@extends('layouts.publisher')

@section('title', 'Publisher profile')

@section('content')
    <div class="panel-title">
        <div><h1>Publisher profile</h1><p>This information appears on your public storefront.</p></div>
        @if ($profile)<div>Status: <x-status-badge :status="$profile->status" /></div>@endif
    </div>

    <form method="POST" action="{{ route('publisher.profile.update') }}" enctype="multipart/form-data" novalidate>
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">Store details</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="display_name" label="Store / brand name" :value="$profile?->display_name" required maxlength="100" /></div>
                            <div class="col-md-6"><x-input name="company" label="Company (optional)" :value="$profile?->company" maxlength="150" /></div>
                            <div class="col-md-6"><x-input name="website" type="url" label="Website" :value="$profile?->website" maxlength="255" placeholder="https://" /></div>
                            <div class="col-md-6"><x-input name="whatsapp" label="WhatsApp" :value="$profile?->whatsapp" maxlength="40" /></div>
                            <div class="col-md-6"><x-select name="country" label="Country" :options="array_combine($countries, $countries)" :value="$profile?->country" placeholder="Select country" /></div>
                        </div>
                        <x-textarea name="bio" label="About your store" :value="$profile?->bio" rows="4" maxlength="2000" />
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Account</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="name" label="Your name" :value="$user->name" required maxlength="100" /></div>
                            <div class="col-md-6"><x-input name="email" type="email" label="Email" :value="$user->email" required maxlength="150" /></div>
                            <div class="col-md-6"><x-input name="phone" label="Phone" :value="$user->phone" maxlength="40" /></div>
                            <div class="col-md-6"><x-input name="username_display" label="Username" :value="'@'.$user->username" disabled help="Your storefront URL: /publisher/{{ $user->username }}" /></div>
                        </div>
                        <p class="small text-muted-2 mb-0">Change your password from <a href="{{ route('account.profile') }}">My account &rarr; Profile</a>.</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">Store logo</div>
                    <div class="card-body text-center">
                        <img id="logoPreview" src="{{ $profile?->logoUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview square rounded-circle mb-3" alt="Logo preview">
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#logoPreview" aria-label="Store logo">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header">Profile photo</div>
                    <div class="card-body text-center">
                        <img id="avatarPreview" src="{{ $user->avatarUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview square rounded-circle mb-3" alt="Avatar preview">
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#avatarPreview" aria-label="Profile photo">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading="Saving..."><i class="bi bi-save"></i> Save profile</button>
            </div>
        </div>
    </form>
@endsection
