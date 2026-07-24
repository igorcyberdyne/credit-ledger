<?php

namespace App\Mapper;

use App\Dto\Response\Domain\ShopResponse;
use App\Entity\Shop;

final readonly class ShopMapper
{
    public static function toResponse(
        Shop $shop,
    ): ShopResponse {
        return new ShopResponse(
            name: $shop->getName(),
            address: $shop->getAddress(),
            postalCode: $shop->getPostalCode(),
            city: $shop->getCity(),
            country: $shop->getCountry(),
            phone: $shop->getPhone(),
            currency: $shop->getCurrency(),
        );
    }
}
