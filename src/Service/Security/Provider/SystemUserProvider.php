<?php

namespace App\Service\Security\Provider;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SystemUserProvider
{
    public const string USER_SYSTEM_EMAIL = 'credit-ledger-app@system.com';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getSystemUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => self::USER_SYSTEM_EMAIL,
        ]);
    }
}
