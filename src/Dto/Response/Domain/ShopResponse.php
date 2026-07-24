<?php

namespace App\Dto\Response\Domain;

use App\Enum\CurrencyEnum;

class ShopResponse
{
    public function __construct(
        public string $name,
        public ?string $address,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?string $phone,
        public CurrencyEnum $currency,
    ) {
    }
}
