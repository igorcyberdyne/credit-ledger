<?php

namespace App\Tests\Tools;

use App\Entity\Shop;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait EntityTrait
{
    abstract protected function getGenerator(): Generator;

    abstract protected function getEntityManager(): EntityManagerInterface;

    abstract protected function getUserPasswordHasherInterface(): UserPasswordHasherInterface;

    public function createUser(
        Shop $shop,
        ?string $email = null,
        array $roles = [],
    ): User {
        $user = new User();

        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setFirstName($this->getGenerator()->firstName());
        $user->setLastName($this->getGenerator()->lastName());
        $user->setPhone($this->getGenerator()->phoneNumber());

        $user->setShop($shop);

        $user->setPassword($this->getUserPasswordHasherInterface()->hashPassword($user, $email));

        return $user;
    }
}
