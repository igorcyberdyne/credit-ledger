<?php

namespace App\Tests\Tools;

final readonly class ApiRoute
{
    public function __construct(
        public string $name,
        public string $method,
        public string $path,
    ) {
    }
}
