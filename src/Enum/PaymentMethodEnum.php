<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentMethodEnum: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
    case TRANSFER = 'TRANSFER';
    case CHECK = 'CHECK';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Espèces',
            self::CARD => 'Carte bancaire',
            self::TRANSFER => 'Virement',
            self::CHECK => 'Chèque',
            self::OTHER => 'Autre',
        };
    }

    public static function choices(): array
    {
        return array_column(
            array_map(
                fn (self $method) => [
                    'label' => $method->label(),
                    'value' => $method,
                ],
                self::cases()
            ),
            'value',
            'label'
        );
    }

    public static function required(): array
    {
        return array_column(self::cases(), 'value');
    }
}
