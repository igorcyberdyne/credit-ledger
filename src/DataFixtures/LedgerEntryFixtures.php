<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Entity\User;
use App\Enum\LedgerTypeEnum;
use App\Enum\PaymentMethodEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Random\RandomException;

final class LedgerEntryFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private const array PRODUCTS = [
        'Bière', 'Café', 'Sandwich', 'Coca-Cola', 'Pizza', 'Pain', 'Croissant', 'Eau', 'Chips', 'Panini',
    ];

    /**
     * @throws RandomException
     */
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= CustomerFixtures::CUSTOMER_COUNT; ++$i) {
            /** @var Customer $customer */
            $customer = $this->getReference(CustomerFixtures::CUSTOMER_REFERENCE_PREFIX.$i, Customer::class);

            $shop = $customer->getShop();
            $user = $this->getRandomUser($shop);

            $totalDebt = 0;
            $count = random_int(5, 15);

            for ($j = 0; $j < $count; ++$j) {
                $totalDebt += $this->createDebt($manager, $customer, $shop, $user);
            }

            if ($this->faker->boolean(75)) {
                $remaining = $totalDebt;
                $payments = random_int(1, 3);

                for ($k = 0; $k < $payments && $remaining > 100; ++$k) {
                    if ($k === $payments - 1 && $this->faker->boolean(30)) {
                        $amount = $remaining;
                    } else {
                        $amount = min($remaining, random_int(100, max(100, (int) ($remaining * 0.6))));
                    }
                    $remaining -= $amount;
                    $this->createPayment($manager, $customer, $shop, $user, $amount);
                }
            }
        }

        $manager->flush();
    }

    /**
     * @throws RandomException
     */
    private function createDebt(ObjectManager $manager, Customer $customer, Shop $shop, User $user): int
    {
        $amount = random_int(150, 2500);

        $entry = new LedgerEntry()
            ->setCustomer($customer)
            ->setShop($shop)
            ->setType(LedgerTypeEnum::DEBT)
            ->setAmountInCents($amount)
            ->setDescription($this->faker->randomElement(self::PRODUCTS));

        $manager->persist($entry);

        return $amount;
    }

    /**
     * @throws RandomException
     */
    private function createPayment(ObjectManager $manager, Customer $customer, Shop $shop, User $user, int $amount): void
    {
        $r = random_int(1, 100);
        $method = $r <= 90 ? PaymentMethodEnum::CASH : ($r <= 98 ? PaymentMethodEnum::CARD : PaymentMethodEnum::TRANSFER);

        $label = match ($method) {
            PaymentMethodEnum::CASH => 'Paiement espèces',
            PaymentMethodEnum::CARD => 'Paiement CB',
            PaymentMethodEnum::TRANSFER => 'Virement',
        };

        $entry = new LedgerEntry()
            ->setCustomer($customer)
            ->setShop($shop)
            ->setType(LedgerTypeEnum::PAYMENT)
            ->setPaymentMethod($method)
            ->setAmountInCents($amount)
            ->setDescription($label);

        $manager->persist($entry);
    }

    private function getRandomUser(Shop $shop): User
    {
        $users = $shop->getUsers()->toArray();

        return $users[array_rand($users)];
    }

    public function getDependencies(): array
    {
        return [
            ShopFixtures::class,
            UserFixtures::class,
            CustomerFixtures::class,
        ];
    }
}
