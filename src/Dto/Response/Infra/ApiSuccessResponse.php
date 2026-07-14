<?php

namespace App\Dto\Response\Infra;

final readonly class ApiSuccessResponse
{
    public function __construct(
        public mixed $data,
        public array $meta = [],
        public string $message = '',
    ) {
    }
}
