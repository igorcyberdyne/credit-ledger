<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
use App\Dto\Response\Domain\Ledger\LedgerEntryResponse;
use App\Entity\Shop;
use App\Event\Domain\PaymentCreatedEvent;
use App\Mapper\LedgerEntryMapper;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use App\Validator\LedgerValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class CreatePaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerValidator $validator,
        private LedgerEntryMapper $ledgerEntryMapper,
        private GetCustomerServiceInterface $getCustomerService,
        private CustomerBalanceService $customerBalanceService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function create(
        Shop $shop,
        string $customerUuid,
        CreatePaymentCommand $command,
    ): LedgerEntryResponse {
        $customer = $this->getCustomerService->getCustomerByUuidAndShop($customerUuid, $shop);

        $this->validator->validatePayment(
            customerBalanceInCents: $this->customerBalanceService->getBalanceInCents($customer),
            command: $command,
        );

        return $this->entityManager->wrapInTransaction(
            function () use ($shop, $customer, $command): LedgerEntryResponse {
                $ledgerEntry = $this->ledgerEntryMapper->fromCreatePaymentCommand(
                    customer: $customer,
                    command: $command,
                );
                $ledgerEntry->setShop($shop);

                $this->entityManager->persist($ledgerEntry);

                $this->entityManager->flush();

                $this->eventDispatcher->dispatch(new PaymentCreatedEvent($ledgerEntry));

                return $this->ledgerEntryMapper->toResponse($ledgerEntry);
            }
        );
    }
}
