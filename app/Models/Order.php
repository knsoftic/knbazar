<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Orders are only created and transitioned by OrderService / PaymentService,
 * which build their attribute arrays explicitly (never from raw input).
 */
#[Fillable([
    'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_country',
    'customer_address', 'subtotal', 'discount_total', 'total', 'currency', 'payment_gateway_id',
    'payment_status', 'status', 'transaction_id', 'payment_proof', 'notes', 'admin_notes',
    'paid_at', 'completed_at', 'cancelled_at', 'ip_address', 'user_agent',
])]
class Order extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * The most recent payment submission for this order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id')->withTrashed();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function downloadPermissions(): HasMany
    {
        return $this->hasMany(DownloadPermission::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Approved;
    }

    public function grantsDownloads(): bool
    {
        return $this->isPaid() && in_array($this->status, OrderStatus::downloadable(), true);
    }

    public function canSubmitPayment(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::AwaitingPayment], true)
            && in_array($this->payment_status, [PaymentStatus::Pending, PaymentStatus::Rejected], true);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return (bool) setting('order_cancel_unpaid_allowed', true) && $this->canSubmitPayment();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }
}
