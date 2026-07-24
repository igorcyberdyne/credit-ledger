<?php

namespace App\Dto\Response\Domain\Customer;

class CustomerBalanceResponse
{
    public function __construct(
        public int $balanceInCents,
        public int $totalDebtInCents,
        public int $totalPaidInCents,
        public int $operations,
    ) {
    }
}
