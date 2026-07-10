<?php

namespace App\Dto\Response\Domain;

final readonly class PaginationMetaResponse
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
        public int $pages,
    ) {
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->pages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}
