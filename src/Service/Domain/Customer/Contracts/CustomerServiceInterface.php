<?php

namespace App\Service\Domain\Customer\Contracts;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Dto\Response\Domain\Customer\CustomerResponse;
use App\Entity\Shop;

interface CustomerServiceInterface
{
    public function create(Shop $shop, CreateCustomerCommand $command): CustomerResponse;

    public function update(UpdateCustomerCommand $command, string $customerUuid): CustomerResponse;

    public function getCustomer(Shop $shop, string $customerUuid): CustomerResponse;

    public function archive(string $customerUuid): void;
}
