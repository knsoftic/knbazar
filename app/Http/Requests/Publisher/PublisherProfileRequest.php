<?php

namespace App\Http\Requests\Publisher;

use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublisherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPublisher() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->user()->getKey())],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{5,40}$/'],
            'display_name' => ['required', 'string', 'min:2', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{5,40}$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'logo' => UploadRules::image(),
            'avatar' => UploadRules::image(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }
}
