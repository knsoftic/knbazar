<?php

namespace App\Notifications;

use App\Models\Product;

class ProductRejected extends MarketplaceNotification
{
    public function __construct(public Product $product, public string $reason) {}

    protected function title(): string
    {
        return 'Product needs changes';
    }

    protected function message(): string
    {
        return 'Your product "'.$this->product->name.'" was rejected. Reason: '.$this->reason;
    }

    protected function url(object $notifiable): string
    {
        return route('publisher.products.edit', $this->product);
    }

    protected function icon(): string
    {
        return 'bi-x-circle';
    }

    protected function level(): string
    {
        return 'danger';
    }
}
