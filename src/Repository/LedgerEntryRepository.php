<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
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

    private function createCustomerQueryBuilder(Customer $customer): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->where('l.customer = :customer')
            ->setParameter('customer', $customer);
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

    public function getStatistics(Customer $customer): CustomerBalance
    {
        $result = $this->createQueryBuilder('l')
            ->select(
                '
                COALESCE(SUM(
                    CASE
                        WHEN l.type = :debt THEN l.amountInCents
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
            ->getQuery()
            ->getSingleResult();

        return new CustomerBalance(
            balanceInCents: max(0, (int) $result['balance']),
            totalDebtInCents: (int) $result['totalDebt'],
            totalPaidInCents: (int) $result['totalPaid'],
            operations: (int) $result['operations'],
        );
    }

    public function createCustomerHistoryQueryBuilder(
        Customer $customer,
    ): QueryBuilder {
        return $this->createCustomerQueryBuilder($customer)->orderBy('l.occurredAt', 'DESC');
    }

    /**
     * @return array{
     *     entries:int,
     *     debts:int,
     *     payments:int,
     *     todayDebtInCents:int,
     *     todayPaymentsInCents:int
     * }
     */
    public function getDashboardStatistics(
        Shop $shop,
    ): array {
        $today = new \DateTimeImmutable('today');
        $result = $this->createQueryBuilder('l')
            ->select([
                'COUNT(l.id) AS entries',
                'SUM(CASE WHEN l.type = :debt THEN 1 ELSE 0 END) AS debts',
                'SUM(CASE WHEN l.type = :payment THEN 1 ELSE 0 END) AS payments',
                'COALESCE(SUM(CASE WHEN l.type = :debt AND l.occurredAt >= :today THEN l.amountInCents ELSE 0 END),0) AS todayDebtInCents',
                'COALESCE(SUM(CASE WHEN l.type = :payment AND l.occurredAt >= :today THEN l.amountInCents ELSE 0 END),0) AS todayPaymentsInCents',
            ])
            ->innerJoin('l.customer', 'c')
            ->where('c.shop = :shop')
            ->setParameter('shop', $shop)
            ->setParameter('debt', LedgerTypeEnum::DEBT)
            ->setParameter('payment', LedgerTypeEnum::PAYMENT)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleResult();

        return [
            'entries' => (int) $result['entries'],
            'debts' => (int) $result['debts'],
            'payments' => (int) $result['payments'],
            'todayDebtInCents' => (int) $result['todayDebtInCents'],
            'todayPaymentsInCents' => (int) $result['todayPaymentsInCents'],
        ];
    }
}
