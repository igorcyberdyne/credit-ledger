<?php

namespace App\Dto\Response\Security;

use App\Dto\Response\Domain\ShopResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LoginResponse
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public int $expiresIn,
        #[SerializedName('user')]
        public UserResponse $userResponseDTO,
        #[SerializedName('shop')]
        public ShopResponse $shopResponse,
    ) {
    }
}
