<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Dto\Command\Security\UserCreateCommand;
use App\Dto\Command\Security\UserUpdateCommand;
use App\Entity\Shop;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Mapper\UserMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @param list<UserRoleEnum> $roles
     */
    private function create(
        UserCreateCommand $command,
        array $roles,
        Shop $shop,
    ): User {
        $user = UserMapper::fromCreateUserCommand($command, $roles);
        $user->setShop($shop);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $command->password,
        );

        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        return $user;
    }

    public function createManager(
        UserCreateCommand $command,
        Shop $shop,
    ): User {
        return $this->create(
            command: $command,
            roles: [UserRoleEnum::MANAGER],
            shop: $shop,
        );
    }

    public function createEmployee(
        UserCreateCommand $command,
        Shop $shop,
    ): User {
        return $this->create(
            command: $command,
            roles: [UserRoleEnum::EMPLOYEE],
            shop: $shop,
        );
    }

    public function update(
        User $user,
        UserUpdateCommand $command,
    ): User {
        $user
            ->setFirstname($command->firstname)
            ->setLastname($command->lastname)
            ->setPhone($command->phone);

        return $user;
    }

    public function changePassword(
        User $user,
        string $plainPassword,
    ): User {
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $plainPassword,
            ),
        );

        return $user;
    }

    public function activate(
        User $user,
    ): User {
        $user->setStatus(UserStatusEnum::ACTIVE);

        return $user;
    }

    public function deactivate(
        User $user,
    ): User {
        $user->setStatus(UserStatusEnum::DISABLED);

        return $user;
    }
}
