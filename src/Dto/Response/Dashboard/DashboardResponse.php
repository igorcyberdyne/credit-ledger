<?php

declare(strict_types=1);

namespace App\Dto\Response\Dashboard;

final readonly class DashboardResponse
{
    public function __construct(
        public int $customers,
        public int $customersInDebt,
        public string $totalAmount,
        public string $todayAmount,
    ) {
    }
}
