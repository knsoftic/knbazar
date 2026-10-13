<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product_id', 'order_id', 'download_permission_id', 'is_free', 'ip_address', 'user_agent'])]
class Download extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(DownloadPermission::class, 'download_permission_id');
    }
}
