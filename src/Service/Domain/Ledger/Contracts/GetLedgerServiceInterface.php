<?php

namespace App\Service\Domain\Ledger\Contracts;

use App\Entity\LedgerEntry;
use App\Entity\Shop;

interface GetLedgerServiceInterface
{
    public function getLedgerByUuid(string $uuid): LedgerEntry;

    public function getLedgerByUuidAndShop(string $uuid, Shop $shop): LedgerEntry;
}
