<?php

namespace App\Notifications;

use App\Models\Product;

class ProductApproved extends MarketplaceNotification
{
    public function __construct(public Product $product) {}

    protected function title(): string
    {
        return 'Product approved';
    }

    protected function message(): string
    {
        return $this->product->isPublished()
            ? 'Your product "'.$this->product->name.'" was approved and is now live.'
            : 'Your product "'.$this->product->name.'" was approved and will be published by the team.';
    }

    protected function url(object $notifiable): string
    {
        return route('publisher.products.show', $this->product);
    }

    protected function icon(): string
    {
        return 'bi-check-circle';
    }

    protected function level(): string
    {
        return 'success';
    }
}
