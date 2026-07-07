<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Shop;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Service\Security\SystemUserProvider;
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

        $shops = [$balto, $nono];


        /*
         * Compte System
         */
        $this->createUser(
            manager: $manager,
            shop: $nono,
            firstname: $this->faker->firstName,
            lastname: $this->faker->lastName,
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
            email: 'owner@balto.fr',
            role: UserRoleEnum::OWNER->value,
            reference: self::OWNER_BALTO
        );

        $this->createUser(
            manager: $manager,
            shop: $nono,
            firstname: 'Nicolas',
            lastname: 'Petit',
            email: 'owner@nono.fr',
            role: UserRoleEnum::OWNER->value,
            reference: self::OWNER_NONO
        );

        /*
         * Employés fixes
         */

        foreach ([
            ['Julie', 'Robert'],
            ['Marc', 'Bernard'],
            ['Sarah', 'Moreau'],
            ['Tom', 'Garcia'],
        ] as $employee) {
            $this->createUser(
                manager: $manager,
                shop: $this->faker->randomElement($shops),
                firstname: $employee[0],
                lastname: $employee[1],
                email: strtolower($employee[0]).'@example.fr',
                role: UserRoleEnum::EMPLOYEE->value,
            );
        }

        /*
         * Utilisateurs Faker
         */

        for ($i = 0; $i < 30; ++$i) {
            $firstname = $this->faker->firstName();

            $lastname = $this->faker->lastName();

            $role = $this->faker->boolean(15)
                ? UserRoleEnum::MANAGER->value
                : UserRoleEnum::EMPLOYEE->value;

            $this->createUser(
                manager: $manager,
                shop: $this->faker->randomElement($shops),
                firstname: $firstname,
                lastname: $lastname,
                email: $this->faker->unique()->safeEmail(),
                role: $role,
                phone: $this->faker->phoneNumber()
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
        $user = new User()
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
