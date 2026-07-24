<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
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
    public function getCustomersDebtStatistics(Shop $shop): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS customerId')
            ->addSelect('COALESCE(SUM(
            CASE
                WHEN l.type = :debt THEN l.amountInCents
                WHEN l.type = :payment THEN -l.amountInCents
                ELSE 0
            END
        ), 0) AS balance')
            ->leftJoin('c.ledgerEntries', 'l')
            ->where('c.shop = :shop')
            ->groupBy('c.id')
            ->setParameter('shop', $shop)
            ->setParameter('debt', LedgerTypeEnum::DEBT)
            ->setParameter('payment', LedgerTypeEnum::PAYMENT)
            ->getQuery()
            ->getScalarResult();

        $customers = count($rows);
        $customersWithDebt = 0;
        $totalDebtInCents = 0;

        foreach ($rows as $row) {
            $balance = (int) $row['balance'];

            if ($balance > 0) {
                ++$customersWithDebt;
                $totalDebtInCents += $balance;
            }
        }

        return [
            'customers' => $customers,
            'customersWithDebt' => $customersWithDebt,
            'totalDebtInCents' => $totalDebtInCents,
        ];
    }

    public function createCustomersLedgerHistoryByShopQueryBuilder(
        Shop $shop,
        ?string $query = null,
    ): QueryBuilder {
        $qb = $this
            ->createQueryBuilder('c')
            ->select('c')
            ->addSelect('MAX(l.updatedAt) AS HIDDEN lastLedgerAt')
            ->leftJoin('c.ledgerEntries', 'l')
            ->where('c.shop = :shop')
            ->setParameter('shop', $shop)
            ->groupBy('c.id')
            ->orderBy('lastLedgerAt', 'DESC')
            ->addOrderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
        ;

        $query = empty($query) ? null : strip_tags(trim($query));
        if (!empty($query)) {
            $orStatements = $qb->expr()->orX();

            $orStatements->add('c.firstname LIKE :query')->add('c.lastname LIKE :query')->add('c.phone LIKE :query');
            $qb->setParameter('query', '%'.$query.'%');

            $qb->andWhere($orStatements);
        }

        return $qb;
    }
}
