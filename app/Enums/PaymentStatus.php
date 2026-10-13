<?php

namespace App\Enums;

/**
 * Payment status as tracked on the order.
 */
enum PaymentStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Submitted => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
