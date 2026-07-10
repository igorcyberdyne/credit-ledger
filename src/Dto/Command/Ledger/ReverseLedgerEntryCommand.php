<?php

namespace App\Dto\Command\Ledger;

class ReverseLedgerEntryCommand
{
    public function __construct(
        public ?string $reason = null,
    ) {
    }
}
