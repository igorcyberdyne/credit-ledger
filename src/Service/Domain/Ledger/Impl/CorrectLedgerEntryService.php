<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Ledger\CorrectLedgerEntryCommand;
use App\Dto\Command\Ledger\CreateDebtCommand;
use App\Dto\Command\Ledger\CreatePaymentCommand;
use App\Dto\Command\Ledger\ReverseLedgerEntryCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Service\Domain\Ledger\Contracts\GetLedgerServiceInterface;
use App\ValueObject\Money;
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
                    $ledgerEntry->getUuid()->toRfc4122(),
                    new ReverseLedgerEntryCommand()
                );

                $customer = $ledgerEntry->getCustomer();

                // 2. Création de la nouvelle écriture
                return match ($ledgerEntry->getType()) {
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
            new Money($ledgerEntry->getAmountInCents(), $ledgerEntry->getShop()->getCurrency())->format(),
            new Money($command->amountInCents, $ledgerEntry->getShop()->getCurrency())->format(),
        );
    }
}
