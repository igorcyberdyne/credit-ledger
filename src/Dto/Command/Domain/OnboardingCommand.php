<?php

namespace App\Dto\Command\Domain;

final readonly class OnboardingCommand
{
    public function __construct(
        public string $shopName,
        public string $address,
        public string $postalCode,
        public string $city,
        public string $country,
        public string $shopPhone,
        public ?string $currency,
        public ?string $timezone,

        public string $firstname,
        public ?string $lastname,
        public string $email,
        public string $phone,
        public string $password,
    ) {
    }
}
