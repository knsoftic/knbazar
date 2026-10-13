<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('marketplace:create-admin {email?} {--name=} {--password=} {--skip-if-exists : Do nothing when a Super Admin already exists}', function () {
    $superAdmins = User::query()->whereHas('role', fn ($q) => $q->where('slug', RoleName::SuperAdmin->value));

    if ($this->option('skip-if-exists') && $superAdmins->exists()) {
        $this->info('A Super Admin already exists: '.$superAdmins->value('email').' (skipped).');

        return 0;
    }

    $email = $this->argument('email') ?: $this->ask('Super Admin email');
    $name = $this->option('name') ?: $this->ask('Super Admin name', 'Super Admin');
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

    $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
    $user->forceFill([
        'role_id' => Role::idFor(RoleName::SuperAdmin),
        'username' => app(SlugService::class)->uniqueUsername($email),
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
    ])->save();

    $this->info("Super Admin {$email} created. Sign in at ".url('/login'));

    return 0;
})->purpose('Create a Super Admin account (run RolePermissionSeeder first on a fresh database)');
