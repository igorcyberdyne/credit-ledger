<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Event\Domain\DebtCreatedEvent;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Validator\LedgerValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class CreateDebtService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerValidator $validator,
        private LedgerEntryMapper $ledgerEntryMapper,
        private GetCustomerServiceInterface $getCustomerService,
        private EventDispatcherInterface $eventDispatcher,
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
            function () use ($shop, $customer, $command): LedgerEntryResponse {
                $ledgerEntry = $this->ledgerEntryMapper->fromCreateDebtCommand($customer, $command);
                $ledgerEntry->setShop($shop);

                $this->entityManager->persist($ledgerEntry);

                $this->entityManager->persist($customer);

                $this->entityManager->flush();

                $this->eventDispatcher->dispatch(new DebtCreatedEvent($ledgerEntry));

                return $this->ledgerEntryMapper->toResponse($ledgerEntry);
            }
        );
    }
}
