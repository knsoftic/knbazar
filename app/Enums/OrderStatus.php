<?php

namespace App\Enums;

enum OrderStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case PaymentSubmitted = 'payment_submitted';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingPayment => 'warning',
            self::PaymentSubmitted => 'info',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'secondary',
            self::Refunded => 'dark',
        };
    }

    /**
     * Statuses in which a paid order grants download access.
     *
     * @return array<int, self>
     */
    public static function downloadable(): array
    {
        return [self::Processing, self::Completed];
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded], true);
    }
}
