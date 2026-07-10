<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Ledger\CreatePaymentCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Validator\LedgerValidator;
use Doctrine\ORM\EntityManagerInterface;

readonly class CreatePaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerValidator $validator,
        private LedgerEntryMapper $ledgerEntryMapper,
        private GetCustomerServiceInterface $getCustomerService,
    ) {
    }

    public function create(
        Shop $shop,
        string $customerUuid,
        CreatePaymentCommand $command,
    ): LedgerEntryResponse {
        $customer = $this->getCustomerService->getCustomerByUuidAndShop($customerUuid, $shop);

        $this->validator->validatePayment(
            customer: $customer,
            command: $command,
        );

        return $this->entityManager->wrapInTransaction(
            function () use ($customer, $command): LedgerEntryResponse {
                $ledgerEntry = $this->ledgerEntryMapper->fromCreatePaymentCommand(
                    customer: $customer,
                    command: $command,
                );

                $customer->decreaseBalance($command->amountInCents);

                $this->entityManager->persist($ledgerEntry);

                $this->entityManager->flush();

                return $this->ledgerEntryMapper->toResponse($ledgerEntry);
            }
        );
    }
}
