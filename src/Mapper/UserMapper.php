<?php

namespace App\Mapper;

use App\Dto\Command\Security\UserCreateCommand;
use App\Dto\Response\Security\UserResponse;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;

final readonly class UserMapper
{
    public static function toResponse(
        User $user,
    ): UserResponse {
        return new UserResponse(
            uuid: $user->getUuid()->toRfc4122(),
            email: $user->getEmail(),
            firstName: $user->getFirstname(),
            lastName: $user->getLastname(),
            roles: $user->getRoles(),
        );
    }

    public static function fromCreateUserCommand(
        UserCreateCommand $dto,
        array $roles,
    ): User {
        $roles = array_map(function (mixed $role) {
            if ($role instanceof UserRoleEnum) {
                return $role->value;
            }

            return $role;
        }, $roles);

        $user = new User();
        $user
            ->setFirstname($dto->firstname)
            ->setLastname($dto->lastname)
            ->setEmail(mb_strtolower(trim($dto->email)))
            ->setPhone($dto->phone)
            ->setRoles($roles)
            ->setStatus(UserStatusEnum::DISABLED);

        return $user;
    }
}
