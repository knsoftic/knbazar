<?php

namespace App\Enums;

enum RoleName: string
{
    use HasLabels;

    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Publisher = 'publisher';
    case Customer = 'customer';

    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'primary',
            self::Publisher => 'info',
            self::Customer => 'secondary',
        };
    }
}
