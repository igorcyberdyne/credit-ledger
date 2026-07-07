<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Shop;
use App\Enum\CustomerStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class CustomerFixtures extends BaseFixtures implements DependentFixtureInterface
{
    public const string CUSTOMER_REFERENCE_PREFIX = 'customer.';
    public const int CUSTOMER_COUNT = 110;

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

        $fixedCustomers = [
            ['Mohamed', 'Ben Ali'],
            ['Karim', 'Bouzid'],
            ['Fatou', 'Diallo'],
            ['Lucas', 'Martin'],
            ['Sonia', 'Petit'],
            ['Julie', 'Robert'],
            ['Nicolas', 'Durand'],
            ['Thomas', 'Bernard'],
            ['Claire', 'Moreau'],
            ['Sarah', 'Garcia'],
        ];
        for ($i = 11; $i <= self::CUSTOMER_COUNT; ++$i) {
            $fixedCustomers[] = [
                $this->faker->firstName(),
                $this->faker->lastName(),
            ];
        }

        $index = 1;

        foreach ($fixedCustomers as [$firstname, $lastname]) {
            $customer = new Customer()
                ->setShop($this->faker->randomElement($shops))
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setPhone($this->faker->phoneNumber())
                ->setStatus(CustomerStatusEnum::ACTIVE);

            if ($this->faker->boolean(25)) {
                $customer->setNote(
                    $this->faker->randomElement([
                        'Paye le vendredi',
                        'Client fidèle',
                        'Voisin',
                        'Toujours en espèces',
                        'À relancer si besoin',
                    ])
                );
            }

            $manager->persist($customer);

            $this->addReference(self::CUSTOMER_REFERENCE_PREFIX.$index++, $customer);
        }

        /*
         * 100 clients aléatoires
         */

        for ($i = 0; $i < 100; ++$i) {
            $customer = new Customer()
                ->setShop($this->faker->randomElement($shops))
                ->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setPhone(
                    $this->faker->boolean(70)
                        ? $this->faker->phoneNumber()
                        : null
                )
                ->setStatus(
                    $this->faker->boolean(95)
                        ? CustomerStatusEnum::ACTIVE
                        : CustomerStatusEnum::ARCHIVED
                );

            if ($this->faker->boolean(20)) {
                $customer->setNote(
                    $this->faker->sentence(6)
                );
            }

            $manager->persist($customer);

            $this->addReference(self::CUSTOMER_REFERENCE_PREFIX.$index++, $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ShopFixtures::class,
            UserFixtures::class,
        ];
    }
}
