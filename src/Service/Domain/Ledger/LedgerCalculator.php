<?php

namespace App\Service\Domain\Ledger;

use App\Entity\Customer;
use App\Repository\LedgerEntryRepository;

final readonly class LedgerCalculator
{
    public function __construct(
        private LedgerEntryRepository $repository,
    ) {
    }

    public function balance(Customer $customer): int
    {
        return $this->repository->balance($customer);
    }

    public function totalDebt(Customer $customer): int
    {
        return $this->repository->getTotalDebt($customer);
    }

    public function totalPaid(Customer $customer): int
    {
        return $this->repository->getTotalPaid($customer);
    }

    public function operationCount(Customer $customer): int
    {
        return $this->repository->countEntries($customer);
    }
}
