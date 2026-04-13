<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case Transfer = 'transfer';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::Check => 'Chèque',
            self::Transfer => 'Virement',
            self::External => 'Externe (HelloAsso…)',
        };
    }
}
