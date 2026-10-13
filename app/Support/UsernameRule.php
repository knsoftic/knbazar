<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class UsernameRule
{
    /**
     * Validation rules for a public username (used in /publisher/{username}).
     *
     * @return array<int, mixed>
     */
    public static function rules(?int $ignoreUserId = null, bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:3',
            'max:30',
            'regex:/^[a-z0-9][a-z0-9_\-]*$/',
            Rule::notIn(config('marketplace.reserved_usernames', [])),
            Rule::unique('users', 'username')->ignore($ignoreUserId),
        ];
    }
}
