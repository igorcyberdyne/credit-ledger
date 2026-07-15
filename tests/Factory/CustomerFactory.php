<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Customer;
use App\Entity\Shop;

final class CustomerFactory extends BaseFactory
{
    public static function class(): string
    {
        return Customer::class;
    }

    protected function defaults(): array
    {
        return [
            'shop' => ShopFactory::new(),
            'firstname' => self::faker()->firstName(),
            'lastname' => self::faker()->lastName(),
            'phone' => self::faker()->phoneNumber(),
        ];
    }

    public function newCustomer(Shop $shop): self
    {
        return $this->with([
            'shop' => $shop,
        ]);
    }
}
