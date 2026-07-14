<?php

declare(strict_types=1);

namespace App\Dto\Response\Domain\Dashboard;

final readonly class DashboardResponse
{
    public function __construct(
        public int $customers,
        public int $customersWithDebt,
        public int $ledgerEntries,
        public int $debts,
        public int $payments,
        public int $totalDebtInCents,
        public int $todayDebtInCents,
        public int $todayPaymentsInCents,
    ) {
    }
}
