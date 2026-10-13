<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Models\PaymentGateway;
use Symfony\Component\HttpFoundation\Response;

/**
 * A payment gateway driver. The marketplace ships with the "manual" driver
 * (bank transfer, JazzCash, EasyPaisa, USDT...). Automated gateways (Stripe,
 * PayPal, ...) can be added by implementing this contract and registering
 * the driver in PaymentGatewayManager.
 */
interface PaymentGatewayDriver
{
    public function key(): string;

    public function label(): string;

    /**
     * Manual drivers require the customer to submit transaction details and
     * an admin to verify them.
     */
    public function requiresManualVerification(): bool;

    /**
     * Called right after an order is placed. Returns the response that starts
     * the payment (a redirect to the payment instructions page for manual
     * gateways, or to a hosted checkout for automated gateways).
     */
    public function initiate(Order $order, PaymentGateway $gateway): Response;
}
