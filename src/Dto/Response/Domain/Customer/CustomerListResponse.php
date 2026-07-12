<?php

namespace App\Dto\Response\Domain\Customer;

use App\Dto\Response\Domain\PaginationMetaResponse;

readonly class CustomerListResponse
{
    /**
     * @param CustomerResponse[] $customers
     */
    public function __construct(
        public array $customers,
        public PaginationMetaResponse $pagination,
    ) {
    }
}
