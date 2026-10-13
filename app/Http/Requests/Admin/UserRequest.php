<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\UsernameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Customers and publishers managed by admins with manage_users.
 * Admin accounts are managed separately by Super Admins (AdminUserRequest).
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => UsernameRule::rules($user?->getKey()),
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->getKey())],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in([RoleName::Customer->value, RoleName::Publisher->value])],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'email_verified' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email_verified' => $this->boolean('email_verified'),
        ]);
    }
}
