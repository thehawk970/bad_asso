<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash     = 'cash';
    case Check    = 'check';
    case External = 'external';

    public function label(): string
    {
        return match($this) {
            self::Cash     => 'Espèces',
            self::Check    => 'Chèque',
            self::External => 'Externe (HelloAsso…)',
        };
    }
}
