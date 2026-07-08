<?php

declare(strict_types=1);

namespace App\Dto\Response\Ledger;

final readonly class CustomerLedgerResponse
{
    /**
     * @param LedgerEntryResponse[] $entries
     */
    public function __construct(
        public string $customer,
        public string $balance,
        public array $entries,
    ) {
    }
}
