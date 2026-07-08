<?php

namespace App\Service\Domain\Customer;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Dto\Response\Customer\CustomerBalanceResponse;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Enum\CustomerStatusEnum;
use App\Mapper\CustomerMapper;
use App\Validator\CustomerValidator;
use Doctrine\ORM\EntityManagerInterface;

readonly class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerValidator $customerValidator,
        private CustomerBalanceService $customerBalanceService,
        private CustomerMapper $customerMapper,
    ) {
    }

    public function create(Shop $shop, CreateCustomerCommand $command): Customer
    {
        // validation
        $this->customerValidator->validateCreate(
            $shop,
            $command
        );

        // conversion
        $customer = $this->customerMapper->fromCreateCommand($command);

        // création
        $customer->setShop($shop);

        // persist
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    public function update(
        Customer $customer,
        UpdateCustomerCommand $request,
    ): Customer {
        $this->customerValidator->validateUpdate(
            $customer,
            $request
        );

        $customer = $this->customerMapper->updateEntity(
            $customer,
            $request
        );

        $this->entityManager->flush();

        return $customer;
    }

    public function archive(
        Customer $customer,
    ): void {
        $customer->setStatus(CustomerStatusEnum::ARCHIVED);

        $this->entityManager->flush();
    }

    public function getStatistics(Customer $customer): CustomerBalanceResponse
    {
        $customerBalance = $this->customerBalanceService->getStatistics($customer);

        return new CustomerBalanceResponse(
            balanceInCents: $customerBalance->balanceInCents,
            totalDebtInCents: $customerBalance->totalDebtInCents,
            totalPaidInCents: $customerBalance->totalPaidInCents,
            operations: $customerBalance->operations,
        );
    }
}
