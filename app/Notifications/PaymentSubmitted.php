<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentSubmitted extends MarketplaceNotification
{
    public function __construct(public Payment $payment) {}

    protected function title(): string
    {
        return 'Payment submitted';
    }

    protected function message(): string
    {
        return 'Payment of '.money($this->payment->amount, $this->payment->currency).' for order '
            .$this->payment->order->order_number.' is waiting for verification.';
    }

    protected function url(object $notifiable): string
    {
        return route('admin.payments.show', $this->payment);
    }

    protected function icon(): string
    {
        return 'bi-credit-card';
    }

    protected function level(): string
    {
        return 'warning';
    }
}
