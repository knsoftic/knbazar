<?php

namespace App\Http\Requests;

use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_gateway_id' => ['required', 'integer', Rule::exists('payment_gateways', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'transaction_id' => ['required', 'string', 'min:3', 'max:150', 'regex:/^[A-Za-z0-9\-_.#\/: ]+$/'],
            'sender_name' => ['nullable', 'string', 'max:150'],
            'sender_account' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'proof' => UploadRules::image(),
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_id.regex' => 'The transaction ID may only contain letters, numbers, spaces and - _ . # / :',
            'proof.image' => 'The payment screenshot must be an image.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_gateway_id' => 'payment method',
            'proof' => 'payment screenshot',
            'sender_account' => 'sender account / number',
        ];
    }
}
