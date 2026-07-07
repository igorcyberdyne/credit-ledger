<?php

declare(strict_types=1);

namespace App\Enum;

enum CustomerStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::ARCHIVED => 'Archivé',
        };
    }

    public static function choices(): array
    {
        return array_column(
            array_map(
                fn (self $status) => [
                    'label' => $status->label(),
                    'value' => $status,
                ],
                self::cases()
            ),
            'value',
            'label'
        );
    }
}
