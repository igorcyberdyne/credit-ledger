<?php

namespace App\Service\Security;

use App\Entity\User;

interface CurrentUserProviderInterface
{
    public function getUser(): ?User;
}
