<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Shop;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Service\Security\Provider\SystemUserProvider;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends BaseFixtures implements DependentFixtureInterface
{
    public const string OWNER_BALTO = 'user.owner.balto';
    public const string OWNER_NONO = 'user.owner.nono';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Shop $balto */
        $balto = $this->getReference(
            ShopFixtures::SHOP_BALTO,
            Shop::class
        );

        /** @var Shop $nono */
        $nono = $this->getReference(
            ShopFixtures::SHOP_NONO,
            Shop::class
        );

        $emailDomainBalto = 'balto.fr';
        $emailDomainNono = 'nono.fr';
        $shops = [
            $emailDomainBalto => $balto,
            $emailDomainNono => $nono,
        ];

        /*
         * Compte System
         */
        $this->createUser(
            manager: $manager,
            shop: $nono,
            firstname: 'Gogo',
            lastname: 'GAMATH',
            email: SystemUserProvider::USER_SYSTEM_EMAIL,
            role: UserRoleEnum::SYSTEM->value
        );

        /*
         * Comptes connus
         */

        $this->createUser(
            manager: $manager,
            shop: $balto,
            firstname: 'Paul',
            lastname: 'Martin',
            email: sprintf('%s@%s', 'manager', $emailDomainBalto),
            role: UserRoleEnum::MANAGER->value,
            reference: self::OWNER_BALTO
        );

        $this->createUser(
            manager: $manager,
            shop: $nono,
            firstname: 'Nicolas',
            lastname: 'Petit',
            email: sprintf('%s@%s', 'manager', $emailDomainNono),
            role: UserRoleEnum::MANAGER->value,
            reference: self::OWNER_NONO
        );

        /*
         * Employés fixes
         */
        foreach ([
            ['Julie', 'Robert', $emailDomainBalto],
            ['Julie', 'Robert', $emailDomainNono],
            ['Sarah', 'Moreau', $emailDomainBalto],
            ['Sarah', 'Moreau', $emailDomainNono],
            ['Employee', 'Oko', $emailDomainBalto],
            ['Employee', 'Oko', $emailDomainNono],
        ] as $employee) {
            $emailDomain = $employee[2];
            $this->createUser(
                manager: $manager,
                shop: $shops[$emailDomain],
                firstname: $employee[0],
                lastname: $employee[1],
                email: sprintf('%s@%s', strtolower($employee[0]), $emailDomain),
                role: UserRoleEnum::EMPLOYEE->value,
            );
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        Shop $shop,
        string $firstname,
        string $lastname,
        string $email,
        string $role,
        ?string $phone = null,
        ?string $reference = null,
    ): void {
        $user = (new User())
            ->setShop($shop)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setPhone($phone)
            ->setRoles([$role])
            ->setStatus(UserStatusEnum::ACTIVE);

        $user->setPassword($this->passwordHasher->hashPassword($user, $email));

        $manager->persist($user);

        if (null !== $reference) {
            $this->addReference($reference, $user);
        }
    }

    public function getDependencies(): array
    {
        return [
            ShopFixtures::class,
        ];
    }
}
