<?php

namespace App\Service\Domain\Customer;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Entity\Customer;
use App\Entity\Shop;

interface CustomerServiceInterface
{
    public function create(Shop $shop, CreateCustomerCommand $command): Customer;

    public function update(Customer $customer, UpdateCustomerCommand $request): Customer;
}
