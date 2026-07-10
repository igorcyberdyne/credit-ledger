<?php

namespace App\Mapper;

use App\Dto\Response\Security\UserResponse;
use App\Entity\User;

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
}
