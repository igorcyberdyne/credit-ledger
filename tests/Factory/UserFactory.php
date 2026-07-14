<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFactory extends BaseFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'roles' => [UserRoleEnum::EMPLOYEE],
            'shop' => ShopFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user, array $a, UserFactory $a3): void {
                /** @var UserPasswordHasherInterface $hasher */
                $hasher = $a3->getService(UserPasswordHasherInterface::class);

                $user->setPassword(
                    $hasher->hashPassword(
                        $user,
                        'password',
                    )
                );
            });
    }
}
