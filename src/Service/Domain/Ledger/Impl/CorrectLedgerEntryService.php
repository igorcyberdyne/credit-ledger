<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Domain\Ledger\CorrectLedgerEntryCommand;
use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Exception\Domain\Ledger\LedgerEntryCannotBeReversedException;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;

readonly class CorrectLedgerEntryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CreateDebtService $createDebtService,
        private CreatePaymentService $createPaymentService,
        private GetLedgerServiceInterface $getLedgerService,
        private LedgerEntryMapper $ledgerEntryMapper,
    ) {
    }

    /**
     * Corrige une écriture en créant son inverse puis une nouvelle écriture.
     */
    public function correct(
        Shop $shop,
        string $ledgerUuid,
        CorrectLedgerEntryCommand $command,
    ): LedgerEntryResponse {
        $ledgerEntry = $this->getLedgerService->getLedgerByUuidAndShop($ledgerUuid, $shop);

        if (!$ledgerEntry->canBeReversed()) {
            throw new LedgerEntryCannotBeReversedException('Cette écriture ne peut pas être annulée.');
        }

        return $this->entityManager->wrapInTransaction(
            function () use (
                $shop,
                $ledgerEntry,
                $command,
            ): LedgerEntryResponse {
                /**
                 * 1. On annule la correction.
                 */
                $reverse = $ledgerEntry->reverse();
                $reverse->setShop($shop);
                $this->entityManager->persist($reverse);

                /**
                 * 2. Création de la nouvelle écriture.
                 */
                $customer = $ledgerEntry->getCustomer();
                $entryResponse = match ($ledgerEntry->getType()) {
                    LedgerTypeEnum::DEBT => $this->createDebtService->create(
                        shop: $shop,
                        customerUuid: $customer->getUuid()->toRfc4122(),
                        command: new CreateDebtCommand(
                            amountInCents: $command->amountInCents,
                            description: $this->formatDescription(
                                $command,
                                $ledgerEntry
                            ),
                        ),
                    ),
                    LedgerTypeEnum::PAYMENT => $this->createPaymentService->create(
                        shop: $shop,
                        customerUuid: $customer->getUuid()->toRfc4122(),
                        command: new CreatePaymentCommand(
                            amountInCents: $command->amountInCents,
                            paymentMethod: $command->paymentMethod,
                            description: $this->formatDescription(
                                $command,
                                $ledgerEntry
                            ),
                        ),
                    ),
                };

                $entry = $this->getLedgerService->getLedgerByUuid($entryResponse->uuid);
                $entry->setCorrectedEntry($ledgerEntry);
                $ledgerEntry->addCorrection($entry);

                $this->entityManager->flush();

                return $this->ledgerEntryMapper->toResponse($entry);
            }
        );
    }

    private function formatDescription(
        CorrectLedgerEntryCommand $command,
        LedgerEntry $ledgerEntry,
    ): string {
        return $command->description ?? sprintf(
            'Correction%s : %s -> %s',
            !empty($ledgerEntry->getDescription()) ? "({$ledgerEntry->getDescription()})" : '',
            $ledgerEntry->getAmountFormat(),
            new Money($command->amountInCents, $ledgerEntry->getShop()->getCurrency())->format(),
        );
    }
}
