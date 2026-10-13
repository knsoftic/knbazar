<?php

namespace App\Notifications;

use App\Models\Product;

class ProductSubmitted extends MarketplaceNotification
{
    public function __construct(public Product $product) {}

    protected function title(): string
    {
        return 'Product submitted for review';
    }

    protected function message(): string
    {
        return '"'.$this->product->name.'" was submitted by '.$this->product->publisherName().' and is waiting for review.';
    }

    protected function url(object $notifiable): string
    {
        return route('admin.products.show', $this->product);
    }

    protected function icon(): string
    {
        return 'bi-box-seam';
    }

    protected function level(): string
    {
        return 'warning';
    }
}
