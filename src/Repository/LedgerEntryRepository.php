<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Enum\LedgerTypeEnum;
use App\ValueObject\CustomerBalance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class LedgerEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LedgerEntry::class);
    }

    public function getBalance(Customer $customer): int
    {
        $qb = $this->createQueryBuilder('l');

        $balance = $qb
            ->select(
                'COALESCE(SUM(
                    CASE
                        WHEN l.type = :debt THEN l.amountInCents
                        WHEN l.type = :payment THEN -l.amountInCents
                        ELSE l.amountInCents
                    END
                ), 0)'
            )
            ->where('l.customer = :customer')
            ->setParameter('customer', $customer)
            ->setParameter('debt', LedgerTypeEnum::DEBT)
            ->setParameter('payment', LedgerTypeEnum::PAYMENT)
            ->getQuery()
            ->getSingleScalarResult();

        return max(0, (int) $balance);
    }

    public function getTotalDebt(Customer $customer): int
    {
        return (int) $this->createCustomerQueryBuilder($customer)
            ->select('COALESCE(SUM(l.amountInCents), 0)')
            ->andWhere('l.type = :type')
            ->setParameter('type', LedgerTypeEnum::DEBT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalPaid(Customer $customer): int
    {
        return (int) $this->createCustomerQueryBuilder($customer)
            ->select('COALESCE(SUM(l.amountInCents), 0)')
            ->andWhere('l.type = :type')
            ->setParameter('type', LedgerTypeEnum::PAYMENT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEntries(Customer $customer): int
    {
        return (int) $this->createCustomerQueryBuilder($customer)
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createCustomerQueryBuilder(Customer $customer): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->where('l.customer = :customer')
            ->setParameter('customer', $customer);
    }

    public function getStatistics(Customer $customer): CustomerBalance
    {
        $result = $this->createQueryBuilder('l')
            ->select(
                '
                COALESCE(SUM(
                    CASE
                        WHEN l.type = :debt THEN l.amountInCents
                        WHEN l.type = :adjustment THEN l.amountInCents
                        WHEN l.type = :payment THEN -l.amountInCents
                        ELSE 0
                    END
                ),0) AS balance,

                COALESCE(SUM(
                    CASE
                        WHEN l.type = :debt
                        THEN l.amountInCents
                        ELSE 0
                    END
                ),0) AS totalDebt,

                COALESCE(SUM(
                    CASE
                        WHEN l.type = :payment
                        THEN l.amountInCents
                        ELSE 0
                    END
                ),0) AS totalPaid,

                COUNT(l.id) AS operations
                '
            )
            ->where('l.customer = :customer')
            ->setParameter('customer', $customer)
            ->setParameter('debt', LedgerTypeEnum::DEBT)
            ->setParameter('payment', LedgerTypeEnum::PAYMENT)
            ->setParameter('adjustment', LedgerTypeEnum::ADJUSTMENT)
            ->getQuery()
            ->getSingleResult();

        return new CustomerBalance(
            balanceInCents: max(0, (int) $result['balance']),
            totalDebtInCents: (int) $result['totalDebt'],
            totalPaidInCents: (int) $result['totalPaid'],
            operations: (int) $result['operations'],
        );
    }
}
