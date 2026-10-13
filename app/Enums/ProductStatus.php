<?php

namespace App\Enums;

enum ProductStatus: string
{
    use HasLabels;

    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Published => 'success',
            self::Unpublished => 'dark',
        };
    }
}
