@extends('account.layout')

@section('account')
    <div class="panel-title"><div><h1>Profile</h1><p>Update your personal details and password.</p></div></div>

    <div class="card mb-4">
        <div class="card-header">Personal information</div>
        <div class="card-body">
            <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf @method('PUT')
                <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                    <img id="avatarPreview" src="{{ $user->avatarUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview square rounded-circle" alt="Profile photo">
                    <div>
                        <label for="avatar" class="form-label mb-1">Profile photo</label>
                        <input type="file" name="avatar" id="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#avatarPreview">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($user->avatar)
                            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="removeAvatar"><label class="form-check-label small" for="removeAvatar">Remove photo</label></div>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6"><x-input name="name" label="Full name" :value="$user->name" required maxlength="100" /></div>
                    <div class="col-md-6"><x-input name="email" type="email" label="Email" :value="$user->email" required maxlength="150" /></div>
                    <div class="col-md-6"><x-input name="phone" label="Phone / WhatsApp" :value="$user->phone" maxlength="40" /></div>
                    <div class="col-md-6"><x-select name="country" label="Country" :options="array_combine($countries, $countries)" :value="$user->country" placeholder="Select country" /></div>
                </div>
                <x-textarea name="address" label="Address" :value="$user->address" rows="2" maxlength="500" />
                <button type="submit" class="btn btn-primary" data-loading="Saving...">Save changes</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Change password</div>
        <div class="card-body">
            <form method="POST" action="{{ route('account.password.update') }}" novalidate>
                @csrf @method('PUT')
                @php $pwErrors = $errors->getBag('password'); @endphp
                <div class="row">
                    @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                        <div class="col-md-4 mb-3">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input type="password" name="{{ $field }}" id="{{ $field }}" class="form-control {{ $pwErrors->has($field) ? 'is-invalid' : '' }}" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required>
                            @if ($pwErrors->has($field))<div class="invalid-feedback">{{ $pwErrors->first($field) }}</div>@endif
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-outline-primary" data-loading="Updating...">Update password</button>
            </form>
        </div>
    </div>
@endsection
