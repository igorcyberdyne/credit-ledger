<?php

declare(strict_types=1);

namespace App\Dto\Criteria\Customer;

class SearchCustomerCriteria
{
    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
