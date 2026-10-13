<?php

namespace App\Http\Requests\Admin;

use App\Payments\PaymentGatewayManager;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_payment_gateways') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'driver' => ['required', Rule::in(array_keys(app(PaymentGatewayManager::class)->options()))],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'details' => ['nullable', 'array', 'max:20'],
            'details.*.label' => ['nullable', 'string', 'max:60', 'required_with:details.*.value'],
            'details.*.value' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(array_keys(config('marketplace.currencies')))],
            'requires_proof' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'logo' => UploadRules::image(),
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['details.*.label.required_with' => 'Each account field with a value needs a label.'];
    }

    /**
     * Detail rows with empty pairs removed.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function detailRows(): array
    {
        return collect($this->validated('details', []))
            ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['value'] ?? null))
            ->map(fn ($row) => ['label' => trim($row['label']), 'value' => trim($row['value'])])
            ->values()
            ->all();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_proof' => $this->boolean('requires_proof'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
