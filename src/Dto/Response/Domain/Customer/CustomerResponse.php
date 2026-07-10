<?php

namespace App\Dto\Response\Domain\Customer;

class CustomerResponse
{
    public function __construct(
        public string $uuid,
        public string $firstname,
        public ?string $lastname,
        public ?string $phone,
        public ?string $balance,
    ) {
    }
}
