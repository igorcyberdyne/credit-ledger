<?php

namespace App\Service\Security\Provider;

use App\Entity\User;

interface CurrentUserProviderInterface
{
    public function getUser(): ?User;
}
