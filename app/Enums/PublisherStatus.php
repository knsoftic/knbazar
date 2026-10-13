<?php

namespace App\Enums;

enum PublisherStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Suspended => 'dark',
        };
    }
}
