<?php

namespace App\Enum;

enum CurrencyEnum: string
{
    case EURO = 'EURO';
    case USD = 'USD';
    case CD = 'CD';
    case XOF = 'XOF';
    case XAF = 'XAF';

    public function symbol(): string
    {
        return match ($this) {
            self::EURO => '€',
            self::USD, self::CD => '$',
            self::XOF, self::XAF => 'FCFA',
        };
    }
}
