<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Bank '.Str::title(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'driver' => 'manual',
            'description' => 'Pay by bank transfer.',
            'instructions' => 'Transfer the order total and submit the transaction ID.',
            'details' => [
                ['label' => 'Account Title', 'value' => 'KN Bazar'],
                ['label' => 'Account Number', 'value' => '0123456789'],
            ],
            'currency' => 'PKR',
            'requires_proof' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function requiresProof(): static
    {
        return $this->state(fn () => ['requires_proof' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
