<?php

namespace App\Enums;

enum UserStatus: string
{
    use HasLabels;

    case Active = 'active';
    case Suspended = 'suspended';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }
}
