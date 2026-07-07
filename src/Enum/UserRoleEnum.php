<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRoleEnum: string
{
    case SYSTEM = 'ROLE_SYSTEM';
    case OWNER = 'ROLE_OWNER';
    case MANAGER = 'ROLE_MANAGER';
    case EMPLOYEE = 'ROLE_EMPLOYEE';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propriétaire',
            self::MANAGER => 'Manager',
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
