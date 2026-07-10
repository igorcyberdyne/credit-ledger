<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Ledger\CreateDebtCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Validator\LedgerValidator;
use Doctrine\ORM\EntityManagerInterface;

readonly class CreateDebtService
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
        CreateDebtCommand $command,
    ): LedgerEntryResponse {
        $customer = $this->getCustomerService->getCustomerByUuidAndShop($customerUuid, $shop);

        $this->validator->validateDebt($command);

        return $this->entityManager->wrapInTransaction(
            function () use ($customer, $command): LedgerEntryResponse {
                $customer->increaseBalance($command->amountInCents);

                $ledgerEntry = $this->ledgerEntryMapper->fromCreateDebtCommand($customer, $command);

                $this->entityManager->persist($ledgerEntry);

                $this->entityManager->persist($customer);

                $this->entityManager->flush();

                return $this->ledgerEntryMapper->toResponse($ledgerEntry);
            }
        );
    }
}
