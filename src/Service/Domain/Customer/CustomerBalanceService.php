<?php

namespace App\Service\Domain\Customer;

use App\Entity\Customer;
use App\Repository\LedgerEntryRepository;
use App\ValueObject\CustomerBalance;

readonly class CustomerBalanceService
{
    public function __construct(
        private LedgerEntryRepository $ledgerRepository,
    ) {
    }

    public function getStatistics(
        Customer $customer,
    ): CustomerBalance {
        return $this->ledgerRepository->getStatistics($customer);
    }
}
