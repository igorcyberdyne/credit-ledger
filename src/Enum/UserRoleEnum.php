<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRoleEnum: string
{
    case SYSTEM = 'ROLE_SYSTEM';
    case MANAGER = 'ROLE_MANAGER';
    case EMPLOYEE = 'ROLE_EMPLOYEE';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => 'System',
            self::MANAGER => 'Gérant',
            self::EMPLOYEE => 'Employé',
        };
    }

    public static function choices(): array
    {
        return array_column(
            array_map(
                fn (self $role) => [
                    'label' => $role->label(),
                    'value' => $role,
                ],
                self::cases()
            ),
            'value',
            'label'
        );
    }
}
