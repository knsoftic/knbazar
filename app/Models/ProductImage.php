<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path', 'thumb_path', 'alt', 'sort_order'])]
class ProductImage extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return storage_url($this->path) ?? asset('assets/img/placeholder.svg');
    }

    public function thumbUrl(): string
    {
        return storage_url($this->thumb_path ?: $this->path) ?? asset('assets/img/placeholder.svg');
    }
}
