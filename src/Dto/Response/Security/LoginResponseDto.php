<?php

namespace App\Dto\Response\Security;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LoginResponseDto
{
    public function __construct(
        public string $token,

        #[SerializedName('user')]
        public UserResponseDto $userResponseDTO,
    ) {
    }
}
