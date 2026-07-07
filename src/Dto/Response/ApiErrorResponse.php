<?php

namespace App\Dto\Response;

final readonly class ApiErrorResponse
{
    public function __construct(
        public string $code,
        public string $message = '',
        public array $details = [],
    ) {
    }
}
