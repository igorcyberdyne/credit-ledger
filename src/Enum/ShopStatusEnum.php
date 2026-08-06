<?php

namespace App\Enum;

enum ShopStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case DISABLED = 'DISABLED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::DISABLED => 'Désactivé',
        };
    }
}
