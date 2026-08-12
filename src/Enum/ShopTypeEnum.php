<?php

namespace App\Enum;

enum ShopTypeEnum: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case BUSINESS = 'BUSINESS';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Particulier',
            self::BUSINESS => 'Commerce',
        };
    }
}
