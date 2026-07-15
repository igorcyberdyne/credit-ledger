<?php

namespace App\Event\Domain;

use App\Entity\LedgerEntry;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractLedgerEvent extends Event
{
    public function __construct(
        private readonly LedgerEntry $ledgerEntry,
    ) {
    }

    public function getLedgerEntry(): LedgerEntry
    {
        return $this->ledgerEntry;
    }
}
