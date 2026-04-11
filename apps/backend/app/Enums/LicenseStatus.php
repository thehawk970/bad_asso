<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Validated  = 'validated';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'En attente',
            self::InProgress => 'En cours',
            self::Validated  => 'Validée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'warning',
            self::InProgress => 'info',
            self::Validated  => 'success',
        };
    }
}
