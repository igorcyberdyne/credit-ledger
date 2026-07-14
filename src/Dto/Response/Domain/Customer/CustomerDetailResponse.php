<?php

declare(strict_types=1);

namespace App\Dto\Response\Domain\Customer;

final readonly class CustomerDetailResponse
{
    public function __construct(
        public string $uuid,
        public string $firstname,
        public ?string $lastname,
        public ?string $phone,
        public ?string $note,
        public string $balance,
        public int $operations,
    ) {
    }
}
