<?php

namespace App\Payments;

use App\Models\PaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\Drivers\ManualGateway;
use InvalidArgumentException;

/**
 * Resolves the driver for a payment gateway record. Register new automated
 * drivers in $drivers (key => class) and select them in the gateway form.
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayDriver>> */
    protected array $drivers = [
        'manual' => ManualGateway::class,
    ];

    public function driver(PaymentGateway $gateway): PaymentGatewayDriver
    {
        $class = $this->drivers[$gateway->driver] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Payment driver [{$gateway->driver}] is not registered.");
        }

        return app($class);
    }

    /**
     * @return array<string, string> key => label
     */
    public function options(): array
    {
        return collect($this->drivers)->mapWithKeys(fn (string $class, string $key) => [$key => app($class)->label()])->all();
    }
}
