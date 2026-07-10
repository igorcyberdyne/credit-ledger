<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Ledger\ReverseLedgerEntryCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Exception\Domain\Ledger\LedgerEntryCannotBeReversedException;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ReverseLedgerEntryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerEntryMapper $ledgerEntryMapper,
        private GetLedgerServiceInterface $getLedgerService,
    ) {
    }

    public function reverse(
        Shop $shop,
        string $ledgerUuid,
        ReverseLedgerEntryCommand $command,
    ): LedgerEntryResponse {
        $ledgerEntry = $this->getLedgerService->getLedgerByUuidAndShop($ledgerUuid, $shop);

        if (!$ledgerEntry->canBeReversed()) {
            throw new LedgerEntryCannotBeReversedException('Cette écriture ne peut pas être annulée.');
        }

        return $this->entityManager->wrapInTransaction(
            function () use ($ledgerEntry, $command): LedgerEntryResponse {
                $customer = $ledgerEntry->getCustomer();

                /**
                 * Création de l'écriture inverse.
                 */
                $reverseEntry = $ledgerEntry->reverse();

                if (null !== $command->reason) {
                    $reverseEntry->setDescription($command->reason);
                }

                /*
                 * Mise à jour du solde du client.
                 */
                $customer->applyLedgerEntry($reverseEntry);

                /*
                 * Sauvegarde de la nouvelle écriture.
                 */
                $this->entityManager->persist($ledgerEntry);

                /*
                 * Doctrine détectera automatiquement
                 * la modification du Customer.
                 */
                $this->entityManager->flush();

                return $this->ledgerEntryMapper->toResponse($reverseEntry);
            }
        );
    }
}
