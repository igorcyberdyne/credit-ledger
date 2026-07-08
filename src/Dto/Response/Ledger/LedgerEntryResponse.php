<?php

declare(strict_types=1);

namespace App\Dto\Response\Ledger;

final readonly class LedgerEntryResponse
{
    public function __construct(
        public string $uuid,
        public string $type,
        public string $description,
        public string $amount,
        public string $occurredAt,
    ) {
    }
}
