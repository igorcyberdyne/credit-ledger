<?php

namespace App\ValueObject;

use App\Entity\Shop;
use App\Entity\User;

final readonly class Onboarding
{
    public function __construct(
        public Shop $shop,
        public User $user,
    ) {
    }
}
