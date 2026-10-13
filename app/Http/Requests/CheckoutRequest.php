<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{5,40}$/'],
            'customer_country' => ['required', 'string', 'max:100'],
            'customer_address' => [setting('checkout_require_address') ? 'required' : 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_gateway_id' => ['required', 'integer', Rule::exists('payment_gateways', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_gateway_id.required' => 'Please choose a payment method.',
            'payment_gateway_id.exists' => 'The selected payment method is not available.',
            'customer_phone.regex' => 'Please enter a valid phone / WhatsApp number.',
            'terms.accepted' => 'Please accept the terms to place your order.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'name',
            'customer_email' => 'email',
            'customer_phone' => 'phone / WhatsApp',
            'customer_country' => 'country',
            'customer_address' => 'address',
        ];
    }
}
