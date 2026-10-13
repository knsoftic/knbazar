<?php

namespace App\Http\Requests\Auth;

use App\Support\UsernameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Guests register a full publisher account; signed-in customers only
 * provide their store details to upgrade their existing account.
 */
class PublisherRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'display_name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => UsernameRule::rules($user?->getKey()),
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{5,40}$/'],
            'country' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'guidelines' => ['accepted'],
        ];

        if (! $user) {
            $rules += [
                'name' => ['required', 'string', 'min:2', 'max:100'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guidelines.accepted' => 'Please accept the publisher guidelines.',
            'phone.regex' => 'Please enter a valid phone / WhatsApp number.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'username' => strtolower(trim((string) $this->input('username'))),
        ]);
    }
}
