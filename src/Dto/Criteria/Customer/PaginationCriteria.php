<?php

namespace App\Dto\Criteria\Customer;

class PaginationCriteria
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public ?string $sort = null,
        public string $direction = 'DESC',
        public ?string $q = '',
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
