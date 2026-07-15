<?php

namespace App\Service\Domain;

use App\Dto\Response\Domain\Dashboard\DashboardResponse;
use App\Entity\Shop;
use App\Repository\CustomerRepository;
use App\Repository\LedgerEntryRepository;

readonly class GetDashboardService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private LedgerEntryRepository $ledgerEntryRepository,
    ) {
    }

    public function get(
        Shop $shop,
    ): DashboardResponse {
        $customerStatistics = $this->customerRepository
            ->getCustomersDebtStatistics($shop);

        $ledgerStatistics = $this->ledgerEntryRepository
            ->getDashboardStatistics($shop);

        return new DashboardResponse(
            customers: $customerStatistics['customers'],
            customersWithDebt: $customerStatistics['customersWithDebt'],
            ledgerEntries: $ledgerStatistics['entries'],
            debts: $ledgerStatistics['debts'],
            payments: $ledgerStatistics['payments'],
            totalDebtInCents: $customerStatistics['totalDebtInCents'],
            todayDebtInCents: $ledgerStatistics['todayDebtInCents'],
            todayPaymentsInCents: $ledgerStatistics['todayPaymentsInCents'],
        );
    }
}
