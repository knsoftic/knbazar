<?php

namespace App\Models;

use App\Enums\PaymentRecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'payment_gateway_id', 'user_id', 'amount', 'currency', 'transaction_id', 'sender_name',
    'sender_account', 'proof_path', 'notes', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'ip_address',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentRecordStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PaymentStatusHistory::class)->latest('id');
    }

    public function isReviewable(): bool
    {
        return in_array($this->status, [PaymentRecordStatus::Pending, PaymentRecordStatus::UnderReview], true);
    }
}
