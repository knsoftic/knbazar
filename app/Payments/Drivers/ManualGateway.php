<?php

namespace App\Payments\Drivers;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use Symfony\Component\HttpFoundation\Response;

class ManualGateway implements PaymentGatewayDriver
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Manual payment (verified by admin)';
    }

    public function requiresManualVerification(): bool
    {
        return true;
    }

    public function initiate(Order $order, PaymentGateway $gateway): Response
    {
        return redirect()
            ->route('orders.pay', $order)
            ->with('success', 'Order '.$order->order_number.' placed. Please follow the payment instructions below.');
    }
}
