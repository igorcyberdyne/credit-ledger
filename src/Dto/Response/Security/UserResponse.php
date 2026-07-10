<?php

namespace App\Dto\Response\Security;

final readonly class UserResponse
{
    public function __construct(
        public string $uuid,
        public string $email,
        public string $firstName,
        public ?string $lastName = null,
        public array $roles = [],
    ) {
    }
}
