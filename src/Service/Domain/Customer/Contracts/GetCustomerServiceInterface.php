<?php

namespace App\Service\Domain\Customer\Contracts;

use App\Entity\Customer;
use App\Entity\Shop;

interface GetCustomerServiceInterface
{
    public function getCustomerByUuid(string $uuid): Customer;

    public function getCustomerByUuidAndShop(string $uuid, Shop $shop): Customer;

    public function getCustomerByPhoneAndShop(string $phone, Shop $shop): Customer;
}
