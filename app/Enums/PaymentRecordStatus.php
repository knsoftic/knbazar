<?php

namespace App\Enums;

/**
 * Status of an individual payment submission.
 */
enum PaymentRecordStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
