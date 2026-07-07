<?php

namespace App\DataFixtures;

use App\Entity\Shop;
use Doctrine\Persistence\ObjectManager;

final class ShopFixtures extends BaseFixtures
{
    public const string SHOP_BALTO = 'shop.balto';
    public const string SHOP_NONO = 'shop.nono';

    public function load(ObjectManager $manager): void
    {
        $shops = [
            [
                'name' => 'Le Balto',
                'slug' => 'le-balto',
                'address' => '12 rue Victor Hugo',
                'postalCode' => '75011',
                'city' => 'Paris',
            ],
            [
                'name' => 'Chez Nono',
                'slug' => 'chez-nono',
                'address' => '5 avenue de la République',
                'postalCode' => '69003',
                'city' => 'Lyon',
            ],
        ];

        foreach ($shops as $index => $data) {
            $shop = new Shop()
                ->setName($data['name'])
                ->setSlug($data['slug'])
                ->setAddress($data['address'])
                ->setPostalCode($data['postalCode'])
                ->setCity($data['city']);

            $manager->persist($shop);

            $this->addReference(
                0 === $index
                    ? self::SHOP_BALTO
                    : self::SHOP_NONO,
                $shop
            );
        }

        $manager->flush();
    }
}
