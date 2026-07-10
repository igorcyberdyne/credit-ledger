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

    public function hasDebt(): bool
    {
        return $this->balanceInCents > 0;
    }

    public function isPaid(): bool
    {
        return 0 === $this->balanceInCents;
    }
}
