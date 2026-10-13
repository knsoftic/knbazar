<?php

namespace App\Http\Requests\Admin;

use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_homepage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:60'],
            // Absolute http(s) URL or a site-relative path such as /products?type=free
            'button_url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://[^\s]+|/[^\s]*)$#'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => UploadRules::image(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['button_url.regex' => 'Use a full URL (https://...) or a site path starting with "/".'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
