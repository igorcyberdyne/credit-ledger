<?php

declare(strict_types=1);

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Exception\Domain\Ledger\LedgerEntryException;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UndoCorrectionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CreateDebtService $createDebtService,
        private CreatePaymentService $createPaymentService,
        private GetLedgerServiceInterface $getLedgerService,
        private LedgerEntryMapper $ledgerEntryMapper,
    ) {
    }

    public function undo(
        Shop $shop,
        string $ledgerUuid,
    ): LedgerEntryResponse {
        $correctedEntry = $this->getLedgerService->getLedgerByUuidAndShop($ledgerUuid, $shop);

        if (null === $correctedEntry->getCorrectedEntry()) {
            throw new LedgerEntryException('Cette opération n\'est pas une correction.');
        }

        if (null !== $correctedEntry->getReversal()) {
            throw new LedgerEntryException('Cette correction est déjà annulée.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($shop, $correctedEntry): LedgerEntryResponse {
            /**
             * 1. On annule la correction.
             */
            $reverse = $correctedEntry->reverse();
            $reverse->setShop($shop);
            $this->entityManager->persist($reverse);
            $this->entityManager->flush();

            /**
             * 2. On récupère l'opération d'origine.
             */
            $original = $correctedEntry->getCorrectedEntry();

            /**
             * 3. On recrée l'opération d'origine.
             */
            $customer = $original->getCustomer();
            $entryResponse = match ($original->getType()) {
                LedgerTypeEnum::DEBT => $this->createDebtService->create(
                    shop: $shop,
                    customerUuid: $customer->getUuid()->toRfc4122(),
                    command: new CreateDebtCommand(
                        amountInCents: $original->getAmountInCents(),
                        description: sprintf(
                            'Rétablissement après annulation de correction (%s)',
                            $original->getAmountFormat(),
                        ),
                        occurredAt: new \DateTimeImmutable()->format(DATE_ATOM),
                    ),
                ),
                LedgerTypeEnum::PAYMENT => $this->createPaymentService->create(
                    shop: $shop,
                    customerUuid: $customer->getUuid()->toRfc4122(),
                    command: new CreatePaymentCommand(
                        amountInCents: $original->getAmountInCents(),
                        paymentMethod: $original->getPaymentMethod(),
                        description: sprintf(
                            'Rétablissement après annulation de correction (%s)',
                            $original->getAmountFormat(),
                        ),
                        occurredAt: new \DateTimeImmutable()->format(DATE_ATOM),
                    ),
                ),
            };

            $entry = $this->getLedgerService->getLedgerByUuid($entryResponse->uuid);

            return $this->ledgerEntryMapper->toResponse($entry);
        });
    }
}
