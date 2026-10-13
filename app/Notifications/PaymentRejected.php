<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentRejected extends MarketplaceNotification
{
    public function __construct(public Payment $payment) {}

    protected function title(): string
    {
        return 'Payment rejected';
    }

    protected function message(): string
    {
        return 'Your payment for order '.$this->payment->order->order_number.' was rejected. Reason: '
            .$this->payment->rejection_reason.' You can submit the payment details again.';
    }

    protected function url(object $notifiable): string
    {
        return route('orders.pay', $this->payment->order);
    }

    protected function icon(): string
    {
        return 'bi-exclamation-octagon';
    }

    protected function level(): string
    {
        return 'danger';
    }
}
