<?php

namespace App\Mapper;

use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Enum\LedgerTypeEnum;

final readonly class LedgerEntryMapper
{
    public function toResponse(
        LedgerEntry $entry,
    ): LedgerEntryResponse {
        return new LedgerEntryResponse(
            uuid: $entry->getUuid()->toRfc4122(),
            type: $entry->getType()->value,
            amount: $entry->getAmountDecimal(),
            description: $entry->getDescription(),
            occurredAt: $entry->getOccurredAt()?->format(DATE_ATOM),
            paymentMethod: $entry->getPaymentMethod(),
        );
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function fromCreateDebtCommand(
        Customer $customer,
        CreateDebtCommand $command,
    ): LedgerEntry {
        return new LedgerEntry()->setCustomer($customer)
            ->setType(LedgerTypeEnum::DEBT)
            ->setAmountInCents($command->amountInCents)
            ->setDescription($command->description)
            ->setOccurredAt(!empty($command->occurredAt) ? new \DateTimeImmutable($command->occurredAt) : null);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function fromCreatePaymentCommand(
        Customer $customer,
        CreatePaymentCommand $command,
    ): LedgerEntry {
        return new LedgerEntry()
            ->setCustomer($customer)
            ->setType(LedgerTypeEnum::PAYMENT)
            ->setAmountInCents($command->amountInCents)
            ->setPaymentMethod($command->paymentMethod)
            ->setDescription($command->description)
            ->setOccurredAt(!empty($command->occurredAt) ? new \DateTimeImmutable($command->occurredAt) : null);
    }
}
