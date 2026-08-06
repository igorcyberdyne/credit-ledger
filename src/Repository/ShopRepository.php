<?php

namespace App\Repository;

use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    public function existsByName(string $name): bool
    {
        return null !== $this->createQueryBuilder('s')
                ->select('1')
                ->andWhere('LOWER(s.name) = LOWER(:name)')
                ->setParameter('name', $name)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    }
}
