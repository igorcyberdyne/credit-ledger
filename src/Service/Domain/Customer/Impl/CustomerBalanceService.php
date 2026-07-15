<?php

namespace App\Service\Domain\Customer\Impl;

use App\Dto\Response\Domain\Customer\CustomerBalanceResponse;
use App\Entity\Customer;
use App\Repository\LedgerEntryRepository;

readonly class CustomerBalanceService
{
    public function __construct(
        private LedgerEntryRepository $ledgerRepository,
    ) {
    }

    public function getBalanceInCents(Customer $customer): int
    {
        return $this->ledgerRepository->getBalance($customer);
    }

    public function getStatistics(Customer $customer): CustomerBalanceResponse
    {
        $customerBalance = $this->ledgerRepository->getStatistics($customer);

        return new CustomerBalanceResponse(
            balanceInCents: $customerBalance->balanceInCents,
            totalDebtInCents: $customerBalance->totalDebtInCents,
            totalPaidInCents: $customerBalance->totalPaidInCents,
            operations: $customerBalance->operations,
        );
    }
}
