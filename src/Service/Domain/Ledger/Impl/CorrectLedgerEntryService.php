<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Ledger\CorrectLedgerEntryCommand;
use App\Dto\Command\Ledger\CreateDebtCommand;
use App\Dto\Command\Ledger\CreatePaymentCommand;
use App\Dto\Command\Ledger\ReverseLedgerEntryCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class CorrectLedgerEntryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReverseLedgerEntryService $reverseLedgerEntryService,
        private CreateDebtService $createDebtService,
        private CreatePaymentService $createPaymentService,
        private GetLedgerServiceInterface $getLedgerService,
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

        return $this->entityManager->wrapInTransaction(
            function () use (
                $shop,
                $ledgerEntry,
                $command,
            ): LedgerEntryResponse {
                // 1. Annulation de l'écriture existante
                $this->reverseLedgerEntryService->reverse(
                    $shop,
                    $ledgerEntry,
                    new ReverseLedgerEntryCommand(
                        reason: $command->description ?? 'Correction'
                    )
                );

                $customer = $ledgerEntry->getCustomer();

                // 2. Création de la nouvelle écriture
                return match ($command->type) {
                    LedgerTypeEnum::DEBT => $this->createDebtService->create(
                        shop: $shop,
                        customerUuid: $customer->getUuid()->toRfc4122(),
                        command: new CreateDebtCommand(
                            amountInCents: $command->amountInCents,
                            description: $command->description,
                        ),
                    ),
                    LedgerTypeEnum::PAYMENT => $this->createPaymentService->create(
                        shop: $shop,
                        customerUuid: $customer->getUuid()->toRfc4122(),
                        command: new CreatePaymentCommand(
                            amountInCents: $command->amountInCents,
                            paymentMethod: $command->paymentMethod,
                        ),
                    ),
                };
            }
        );
    }
}
