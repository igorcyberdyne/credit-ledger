<?php

namespace App\Service\Security;

use App\Dto\Response\Security\LoginResponse;
use App\Entity\User;
use App\Mapper\ShopMapper;
use App\Mapper\UserMapper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AuthenticationResponseFactory
{
    public function __construct(
        #[Autowire('%app.jwt_ttl%')]
        private int $jwtTtl,
    ) {
    }

    public function getJwtTtl(): int
    {
        return $this->jwtTtl;
    }

    public function create(
        User $user,
        string $token,
        string $refreshToken,
    ): LoginResponse {
        return new LoginResponse(
            token: $token,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtTtl,
            userResponseDTO: UserMapper::toResponse($user),
            shopResponse: ShopMapper::toResponse($user->getShop()),
        );
    }
}
