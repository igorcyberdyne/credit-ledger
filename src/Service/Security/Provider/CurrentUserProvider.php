<?php

namespace App\Service\Security\Provider;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(
        private Security $security,
        private SystemUserProvider $systemUserProvider,
    ) {
    }

    public function getUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : $this->systemUserProvider->getSystemUser();
    }
}
