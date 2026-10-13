<?php

namespace Database\Factories;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\PublisherProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::idFor(RoleName::Customer),
            'name' => fake()->name(),
            'username' => Str::lower(Str::random(6)).fake()->unique()->numberBetween(100, 999999),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->numerify('+92 3## #######'),
            'country' => 'Pakistan',
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Suspended]);
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role_id' => Role::idFor(RoleName::Customer)]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role_id' => Role::idFor(RoleName::SuperAdmin)]);
    }

    /**
     * @param  array<int, string>  $permissions  permission slugs
     */
    public function admin(array $permissions = []): static
    {
        return $this->state(fn () => ['role_id' => Role::idFor(RoleName::Admin)])
            ->afterCreating(function (User $user) use ($permissions) {
                if ($permissions) {
                    $user->permissions()->sync(Permission::query()->whereIn('slug', $permissions)->pluck('id'));
                }
            });
    }

    public function publisher(PublisherStatus $status = PublisherStatus::Approved): static
    {
        return $this->state(fn () => ['role_id' => Role::idFor(RoleName::Publisher)])
            ->afterCreating(function (User $user) use ($status) {
                $profile = new PublisherProfile([
                    'display_name' => $user->name.' Studio',
                    'bio' => fake()->sentence(16),
                    'country' => $user->country,
                    'website' => 'https://example.com',
                ]);
                $profile->forceFill([
                    'user_id' => $user->getKey(),
                    'status' => $status,
                    'approved_at' => $status === PublisherStatus::Approved ? now() : null,
                ])->save();
            });
    }
}
