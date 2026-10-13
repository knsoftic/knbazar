<?php

namespace App\Notifications;

use App\Enums\PublisherStatus;
use App\Models\PublisherProfile;

class PublisherStatusChanged extends MarketplaceNotification
{
    public function __construct(public PublisherProfile $profile) {}

    protected function title(): string
    {
        return 'Publisher account '.strtolower($this->profile->status->label());
    }

    protected function message(): string
    {
        return match ($this->profile->status) {
            PublisherStatus::Approved => 'Your publisher account was approved. You can now submit products for review.',
            PublisherStatus::Rejected => 'Your publisher application was rejected.'.($this->profile->rejection_reason ? ' Reason: '.$this->profile->rejection_reason : ''),
            PublisherStatus::Suspended => 'Your publisher account was suspended. Please contact support.',
            default => 'Your publisher account status changed to '.$this->profile->status->label().'.',
        };
    }

    protected function url(object $notifiable): string
    {
        return route('publisher.dashboard');
    }

    protected function icon(): string
    {
        return 'bi-shop';
    }
}
