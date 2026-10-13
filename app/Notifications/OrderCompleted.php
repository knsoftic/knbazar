<?php

namespace App\Notifications;

use App\Models\Order;

class OrderCompleted extends MarketplaceNotification
{
    public function __construct(public Order $order) {}

    protected function title(): string
    {
        return 'Order completed';
    }

    protected function message(): string
    {
        return 'Order '.$this->order->order_number.' is complete. Visit your downloads to get your files.';
    }

    protected function url(object $notifiable): string
    {
        return route('downloads.index');
    }

    protected function icon(): string
    {
        return 'bi-bag-check';
    }

    protected function level(): string
    {
        return 'success';
    }
}
