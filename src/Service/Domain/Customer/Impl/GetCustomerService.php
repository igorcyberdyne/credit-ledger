<?php

namespace App\Service\Domain\Customer\Impl;

use App\Entity\Customer;
use App\Entity\Shop;
use App\Exception\Domain\Customer\CustomerNotFoundException;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class GetCustomerService implements GetCustomerServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getCustomerByUuid(string $uuid): Customer
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['uuid' => $uuid]);
        if (!$customer instanceof Customer) {
            throw new CustomerNotFoundException();
        }

        return $customer;
    }

    public function getCustomerByUuidAndShop(string $uuid, Shop $shop): Customer
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['uuid' => $uuid, 'shop' => $shop]);
        if (!$customer instanceof Customer) {
            throw new CustomerNotFoundException();
        }

        return $customer;
    }

    public function getCustomerByPhoneAndShop(string $phone, Shop $shop): Customer
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['phone' => $phone, 'shop' => $shop]);
        if (!$customer instanceof Customer) {
            throw new CustomerNotFoundException();
        }

        return $customer;
    }
}
