<?php

namespace App\Mapper;

use App\Dto\Response\Ledger\LedgerEntryResponse;
use App\Entity\LedgerEntry;
use App\ValueObject\Money;

final readonly class LedgerEntryMapper
{
    public function toResponse(
        LedgerEntry $entry,
    ): LedgerEntryResponse {
        return new LedgerEntryResponse(
            uuid: $entry->getUuid()->toRfc4122(),
            type: $entry->getType()->value,
            description: $entry->getDescription(),
            amount: new Money($entry->getAmountInCents())->decimal(),
            occurredAt: $entry->getOccurredAt()->format(DATE_ATOM),
        );
    }
}
