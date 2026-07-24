<?php

declare(strict_types=1);

namespace App\Dto\Response\Domain\Ledger;

use App\Dto\Response\Domain\Customer\CustomerBalanceResponse;
use App\Dto\Response\Domain\PaginationMetaResponse;

final readonly class CustomerLedgerResponse
{
    /**
     * @param CustomerLedgerItemResponse[] $entries
     */
    public function __construct(
        public CustomerBalanceResponse $statistics,
        public array $entries,
        public PaginationMetaResponse $pagination,
    ) {
    }
}
