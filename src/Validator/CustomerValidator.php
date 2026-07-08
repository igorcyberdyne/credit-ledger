<?php

namespace App\Validator;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Enum\CustomerStatusEnum;
use App\Exception\Domain\Customer\CustomerAlreadyExistsException;
use App\Exception\Domain\Customer\CustomerArchivedException;
use App\Exception\Domain\Customer\CustomerNotFoundException;
use App\Exception\Domain\Customer\CustomerNotFromShopException;
use App\Repository\CustomerRepository;

readonly class CustomerValidator
{
    public function __construct(
        private CustomerRepository $customerRepository,
    ) {
    }

    public function validateCreate(
        Shop $shop,
        CreateCustomerCommand $request,
    ): void {
        $this->validatePhone(
            shop: $shop,
            phone: $request->phone,
        );
    }

    public function validateUpdate(
        Customer $customer,
        UpdateCustomerCommand $request,
    ): void {
        $this->ensureCustomerIsActive($customer);

        $this->validatePhone(
            shop: $customer->getShop(),
            phone: $request->phone,
            ignore: $customer,
        );
    }

    public function validateOwnership(
        Customer $customer,
        Shop $shop,
    ): void {
        if ($customer->getShop()->getId() !== $shop->getId()) {
            throw new CustomerNotFromShopException();
        }
    }

    private function validatePhone(
        Shop $shop,
        ?string $phone,
        ?Customer $ignore = null,
    ): void {
        if (empty($phone)) {
            throw new CustomerNotFoundException();
        }
        $phone = trim($phone);

        $existing = $this->customerRepository->findOneBy([
            'shop' => $shop,
            'phone' => $phone,
        ]);

        if (null === $existing) {
            return;
        }

        if (null !== $ignore && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new CustomerAlreadyExistsException('Ce numéro de téléphone est déjà utilisé.');
    }

    private function ensureCustomerIsActive(
        Customer $customer,
    ): void {
        if (CustomerStatusEnum::ARCHIVED === $customer->getStatus()) {
            throw new CustomerArchivedException();
        }
    }
}
