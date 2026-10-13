<?php

namespace App\Http\Requests;

use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Product create/update validation shared by the publisher and admin forms.
 * Authorisation is handled by route middleware and ProductPolicy.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function isAdminForm(): bool
    {
        return $this->routeIs('admin.*');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $galleryMax = (int) setting('upload_gallery_max', 8);

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:200'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')->when(! $this->isAdminForm(), fn ($rule) => $rule->where('is_active', true))],
            'product_type' => ['required', Rule::in(array_keys(config('marketplace.product_types')))],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'features' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'is_free' => ['boolean'],
            'price' => ['exclude_if:is_free,true', 'required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['exclude_if:is_free,true', 'nullable', 'numeric', 'min:0', 'max:99999999'],
            'version' => ['nullable', 'string', 'max:30'],
            'demo_url' => ['nullable', 'url:http,https', 'max:255'],
            'documentation_url' => ['nullable', 'url:http,https', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'whatsapp_enabled' => ['boolean'],
            'image' => UploadRules::image(),
            'og_image' => UploadRules::image(),
            'gallery' => ['nullable', 'array', 'max:'.$galleryMax],
            'gallery.*' => UploadRules::image(true),
            'file' => UploadRules::productFile(),
            'action' => ['required', Rule::in($this->isAdminForm() ? ['save', 'publish', 'draft'] : ['draft', 'submit'])],
        ];

        if ($this->isAdminForm()) {
            $rules['user_id'] = ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')];
            $rules['is_featured'] = ['boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->boolean('is_free')) {
                    return;
                }

                $price = (float) $this->input('price');
                if ($price <= 0) {
                    $validator->errors()->add('price', 'Paid products need a price greater than zero (or mark the product as free).');
                }

                $sale = $this->input('sale_price');
                if ($sale !== null && $sale !== '' && (float) $sale >= $price) {
                    $validator->errors()->add('sale_price', 'The sale price must be lower than the regular price.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
            'gallery.max' => 'You can upload up to :max gallery images.',
            'gallery.*.max' => 'Each gallery image may not be larger than :max KB.',
            'image.max' => 'The main image may not be larger than :max KB.',
            'file.max' => 'The product file may not be larger than '.setting('upload_file_max_mb').' MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'user_id' => 'owner',
            'file' => 'product file',
            'image' => 'main image',
            'og_image' => 'Open Graph image',
            'gallery.*' => 'gallery image',
        ];
    }

    /**
     * Product attributes (excludes uploads, keywords and workflow inputs).
     *
     * @return array<string, mixed>
     */
    public function productData(): array
    {
        $data = $this->safe()->except(['image', 'og_image', 'gallery', 'file', 'action', 'user_id', 'is_featured', 'keywords']);

        if ($this->boolean('is_free')) {
            $data['price'] = 0;
            $data['sale_price'] = null;
        } elseif (($data['sale_price'] ?? '') === '') {
            $data['sale_price'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function uploads(): array
    {
        return [
            'image' => $this->file('image'),
            'og_image' => $this->file('og_image'),
            'gallery' => array_filter((array) $this->file('gallery', [])),
            'file' => $this->file('file'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_free' => $this->boolean('is_free'),
            'whatsapp_enabled' => $this->boolean('whatsapp_enabled'),
            'is_featured' => $this->boolean('is_featured'),
            'slug' => $this->filled('slug') ? Str::slug((string) $this->input('slug')) : null,
        ]);
    }
}
