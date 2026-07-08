<?php

namespace App\ValueObject;

final readonly class CustomerBalance
{
    public function __construct(
        public int $balanceInCents,
        public int $totalDebtInCents,
        public int $totalPaidInCents,
        public int $operations,
    ) {
    }
}
