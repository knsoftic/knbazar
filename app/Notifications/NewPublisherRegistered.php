<?php

namespace App\Notifications;

use App\Models\User;

class NewPublisherRegistered extends MarketplaceNotification
{
    public function __construct(public User $publisher) {}

    protected function title(): string
    {
        return 'New publisher registration';
    }

    protected function message(): string
    {
        return $this->publisher->publicName().' registered as a publisher'
            .(setting('publisher_require_approval') ? ' and is waiting for approval.' : '.');
    }

    protected function url(object $notifiable): string
    {
        return route('admin.publishers.show', $this->publisher);
    }

    protected function icon(): string
    {
        return 'bi-person-plus';
    }
}
