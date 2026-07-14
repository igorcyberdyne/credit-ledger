<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Exception\Domain\Ledger\LedgerNotFoundException;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class GetLedgerService implements GetLedgerServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getLedgerByUuid(string $uuid): LedgerEntry
    {
        $ledgerEntry = $this->entityManager->getRepository(LedgerEntry::class)->findOneBy(['uuid' => $uuid]);
        if (!$ledgerEntry instanceof LedgerEntry) {
            throw new LedgerNotFoundException();
        }

        return $ledgerEntry;
    }

    public function getLedgerByUuidAndShop(string $uuid, Shop $shop): LedgerEntry
    {
        $ledgerEntry = $this->entityManager->getRepository(LedgerEntry::class)->findOneBy(['uuid' => $uuid, 'shop' => $shop]);
        if (!$ledgerEntry instanceof LedgerEntry) {
            throw new LedgerNotFoundException();
        }

        return $ledgerEntry;
    }
}
