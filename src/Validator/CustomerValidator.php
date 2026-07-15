<?php

namespace App\Validator;

use App\Dto\Command\Domain\Customer\CreateCustomerCommand;
use App\Dto\Command\Domain\Customer\UpdateCustomerCommand;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Enum\CustomerStatusEnum;
use App\Exception\Domain\Customer\CustomerAlreadyExistsException;
use App\Exception\Domain\Customer\CustomerArchivedException;
use App\Exception\Domain\Customer\CustomerMissingPhoneException;
use App\Exception\Domain\Customer\CustomerNotFoundException;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;

readonly class CustomerValidator
{
    public function __construct(
        private GetCustomerServiceInterface $getCustomerService,
    ) {
    }

    public function validateCreate(
        Shop $shop,
        CreateCustomerCommand $command,
    ): void {
        $this->validatePhone(
            shop: $shop,
            phone: $command->phone,
        );
    }

    public function validateUpdate(
        Customer $customer,
        UpdateCustomerCommand $command,
    ): void {
        $this->ensureCustomerIsActive($customer);

        $this->validatePhone(
            shop: $customer->getShop(),
            phone: $command->phone,
            ignore: $customer,
        );
    }

    private function validatePhone(
        Shop $shop,
        ?string $phone,
        ?Customer $ignore = null,
    ): void {
        if (empty($phone)) {
            throw new CustomerMissingPhoneException();
        }

        $phone = trim($phone);

        try {
            $existing = $this->getCustomerService->getCustomerByPhoneAndShop($phone, $shop);
        } catch (CustomerNotFoundException) {
            return;
        }

        if (null !== $ignore && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new CustomerAlreadyExistsException();
    }

    private function ensureCustomerIsActive(
        Customer $customer,
    ): void {
        if (CustomerStatusEnum::ARCHIVED === $customer->getStatus()) {
            throw new CustomerArchivedException();
        }
    }
}
