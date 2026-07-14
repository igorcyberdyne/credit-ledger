<?php

namespace App\Service\Domain\Customer\Impl;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Dto\Response\Domain\Customer\CustomerResponse;
use App\Entity\Shop;
use App\Enum\CustomerStatusEnum;
use App\Exception\Domain\Customer\CustomerNotFoundException;
use App\Mapper\CustomerMapper;
use App\Service\Domain\Customer\Contracts\CustomerServiceInterface;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Validator\CustomerValidator;
use Doctrine\ORM\EntityManagerInterface;

readonly class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerValidator $customerValidator,
        private CustomerMapper $customerMapper,
        private CustomerBalanceService $customerBalanceService,
        private GetCustomerServiceInterface $getCustomerService,
    ) {
    }

    public function getCustomer(Shop $shop, string $customerUuid): CustomerResponse
    {
        $customer = $this->getCustomerService->getCustomerByUuidAndShop($customerUuid, $shop);

        return $this->customerMapper->toResponse($customer, $this->customerBalanceService->getStatistics($customer));
    }

    public function create(Shop $shop, CreateCustomerCommand $command): CustomerResponse
    {
        // Check si le client a été SoftDeleteable afin de le reactiver
        if (!empty($command->phone)) {
            $filters = $this->entityManager->getFilters();
            $filters->disable('softdeleteable');

            try {
                $customer = $this->getCustomerService->getCustomerByPhoneAndShop($command->phone, $shop);
                if (null === $customer->getDeletedAt()) {
                    throw new CustomerNotFoundException();
                }

                $reactivatedCustomer = $this->customerMapper->updateEntity(
                    $customer,
                    $this->customerMapper->fromCreateCustomerCommandToUpdateCustomerCommand($command),
                );
                $reactivatedCustomer
                    ->setDeletedAt(null)
                    ->setDeletedBy(null);

                $this->entityManager->flush();

                return $this->customerMapper->toResponse($reactivatedCustomer, $this->customerBalanceService->getStatistics($reactivatedCustomer));
            } catch (CustomerNotFoundException) {
            } finally {
                $filters->enable('softdeleteable');
            }
        }

        // validation
        $this->customerValidator->validateCreate($shop, $command);

        // conversion
        $customer = $this->customerMapper->fromCreateCustomerCommand($command);

        // création
        $customer->setShop($shop);

        // persist
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $this->customerMapper->toResponse($customer);
    }

    public function update(
        UpdateCustomerCommand $command,
        string $customerUuid,
    ): CustomerResponse {
        $customer = $this->getCustomerService->getCustomerByUuid($customerUuid);

        $this->customerValidator->validateUpdate($customer, $command);

        $customer = $this->customerMapper->updateEntity($customer, $command);

        $this->entityManager->flush();

        return $this->customerMapper->toResponse($customer, $this->customerBalanceService->getStatistics($customer));
    }

    public function archive(
        string $customerUuid,
    ): void {
        $customer = $this->getCustomerService->getCustomerByUuid($customerUuid);

        $customer->setStatus(CustomerStatusEnum::ARCHIVED);

        $this->entityManager->flush();
    }
}
