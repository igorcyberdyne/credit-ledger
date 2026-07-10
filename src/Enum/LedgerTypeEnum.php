<?php

declare(strict_types=1);

namespace App\Enum;

enum LedgerTypeEnum: string
{
    case DEBT = 'DEBT';
    case PAYMENT = 'PAYMENT';

    public function label(): string
    {
        return match ($this) {
            self::DEBT => 'Dette',
            self::PAYMENT => 'Paiement',
        };
    }

    public function isDebt(): bool
    {
        return self::DEBT === $this;
    }

    public function isPayment(): bool
    {
        return self::PAYMENT === $this;
    }

    public static function choices(): array
    {
        return array_column(
            array_map(
                fn (self $type) => [
                    'label' => $type->label(),
                    'value' => $type,
                ],
                self::cases()
            ),
            'value',
            'label'
        );
    }
}
