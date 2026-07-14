<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * @return array{
     *     customers:int,
     *     customersWithDebt:int,
     *     totalDebtInCents:int
     * }
     */
    public function getDashboardStatistics(
        Shop $shop,
    ): array {
        $result = $this->createQueryBuilder('c')
            ->select([
                'COUNT(c.id) AS customers',
                'SUM(CASE WHEN c.balanceInCents > 0 THEN 1 ELSE 0 END) AS customersWithDebt',
                'COALESCE(SUM(c.balanceInCents), 0) AS totalDebtInCents',
            ])
            ->where('c.shop = :shop')
            ->setParameter('shop', $shop)
            ->getQuery()
            ->getSingleResult();

        return [
            'customers' => (int) $result['customers'],
            'customersWithDebt' => (int) $result['customersWithDebt'],
            'totalDebtInCents' => (int) $result['totalDebtInCents'],
        ];
    }

    public function createCustomersLedgerHistoryByShopQueryBuilder(Shop $shop): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.ledgerEntries', 'l')
            ->where('c.shop = :shop')
            ->setParameter('shop', $shop)
        ;
    }
}
