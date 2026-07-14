<?php

namespace App\Mapper;

use App\Dto\Command\Domain\Customer\CreateCustomerCommand;
use App\Dto\Command\Domain\Customer\UpdateCustomerCommand;
use App\Dto\Response\Domain\Customer\CustomerBalanceResponse;
use App\Dto\Response\Domain\Customer\CustomerResponse;
use App\Entity\Customer;

readonly class CustomerMapper
{
    public function fromCreateCustomerCommandToUpdateCustomerCommand(
        CreateCustomerCommand $command,
    ): UpdateCustomerCommand {
        return new UpdateCustomerCommand(
            firstname: $command->firstname,
            lastname: $command->lastname,
            phone: $command->phone,
            note: $command->note,
        );
    }

    public function fromCreateCustomerCommand(
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
        UpdateCustomerCommand $command,
    ): Customer {
        return $customer
            ->setFirstname($command->firstname)
            ->setLastname($command->lastname)
            ->setPhone($command->phone)
            ->setNote($command->note);
    }

    public function toResponse(
        Customer $customer,
        ?CustomerBalanceResponse $customerBalanceResponse = null,
    ): CustomerResponse {
        return new CustomerResponse(
            uuid: $customer->getUuid()->toRfc4122(),
            firstname: $customer->getFirstname(),
            lastname: $customer->getLastname(),
            phone: $customer->getPhone(),
            balance: $customerBalanceResponse,
        );
    }
}
