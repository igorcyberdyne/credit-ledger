<?php

namespace App\Dto\Response\Infra;

final readonly class ApiErrorResponse
{
    public function __construct(
        public int|string $code,
        public string $message = '',
        public array $details = [],
    ) {
    }
}
