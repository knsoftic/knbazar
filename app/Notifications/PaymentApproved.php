<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentApproved extends MarketplaceNotification
{
    public function __construct(public Payment $payment) {}

    protected function title(): string
    {
        return 'Payment approved';
    }

    protected function message(): string
    {
        return 'Your payment for order '.$this->payment->order->order_number.' was approved. Your downloads are now available.';
    }

    protected function url(object $notifiable): string
    {
        return route('orders.show', $this->payment->order);
    }

    protected function icon(): string
    {
        return 'bi-check2-circle';
    }

    protected function level(): string
    {
        return 'success';
    }
}
