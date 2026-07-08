<?php

namespace App\Mapper;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Dto\Response\Customer\CustomerBalanceResponse;
use App\Dto\Response\Customer\CustomerResponse;
use App\Entity\Customer;
use App\ValueObject\Money;

readonly class CustomerMapper
{
    public function fromCreateCommand(
        CreateCustomerCommand $dto,
    ): Customer {
        return new Customer()
            ->setFirstname($dto->firstname)
            ->setLastname($dto->lastname)
            ->setPhone($dto->phone)
            ->setNote($dto->note);
    }

    public function updateEntity(
        Customer $customer,
        UpdateCustomerCommand $dto,
    ): Customer {
        return $customer
            ->setFirstname($dto->firstname)
            ->setLastname($dto->lastname)
            ->setPhone($dto->phone)
            ->setNote($dto->note);
    }

    public function toResponse(
        Customer $customer,
        CustomerBalanceResponse $customerBalanceResponse,
    ): CustomerResponse {
        return new CustomerResponse(
            uuid: $customer->getUuid()->toRfc4122(),
            firstname: $customer->getFirstname(),
            lastname: $customer->getLastname(),
            phone: $customer->getPhone(),
            balance: new Money($customerBalanceResponse->balanceInCents)->decimal(),
        );
    }
}
