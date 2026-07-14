<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Shop;
use App\Enum\CurrencyEnum;

final class ShopFactory extends BaseFactory
{
    public static function class(): string
    {
        return Shop::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'slug' => sprintf('%s-%s', self::faker()->slug(), uniqid()),
            'address' => self::faker()->address(),
            'postalCode' => self::faker()->postcode(),
            'city' => self::faker()->currencyCode(),
            'currency' => CurrencyEnum::EURO,
        ];
    }
}
