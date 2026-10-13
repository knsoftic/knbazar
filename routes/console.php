<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('marketplace:create-admin {email} {--name=Super Admin} {--password=}', function (string $email) {
    $password = $this->option('password') ?: $this->secret('Password (min. 8 characters, letters and numbers)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/']],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = new User(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
    $user->forceFill([
        'role_id' => Role::idFor(RoleName::SuperAdmin),
        'username' => app(SlugService::class)->uniqueUsername($email),
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
    ])->save();

    $this->info("Super Admin {$email} created. Sign in at ".url('/login'));

    return 0;
})->purpose('Create a Super Admin account (run RolePermissionSeeder first on a fresh database)');
