<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'ip_address', 'user_agent'])]
class ContactMessage extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->forceFill(['is_read' => true, 'read_at' => now()])->save();
        }
    }

    public function markAsUnread(): void
    {
        $this->forceFill(['is_read' => false, 'read_at' => null])->save();
    }
}
