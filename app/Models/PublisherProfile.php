<?php

namespace App\Models;

use App\Enums\PublisherStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['display_name', 'company', 'bio', 'website', 'whatsapp', 'country'])]
class PublisherProfile extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PublisherStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logoUrl(): ?string
    {
        return storage_url($this->logo);
    }

    public function isApproved(): bool
    {
        return $this->status === PublisherStatus::Approved;
    }

    /**
     * Profile completeness used on the publisher dashboard.
     */
    public function completeness(): int
    {
        $fields = ['display_name', 'bio', 'website', 'country', 'logo', 'whatsapp'];
        $filled = collect($fields)->filter(fn (string $field) => filled($this->{$field}))->count();

        return (int) round($filled / count($fields) * 100);
    }
}
