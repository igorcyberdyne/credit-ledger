<?php

namespace App\Tests\Service\Domain\Ledger;

use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\CurrencyEnum;
use App\Enum\LedgerTypeEnum;
use App\Service\Domain\Ledger\CustomerLedgerTimelineBuilder;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class CustomerLedgerTimelineBuilderTest extends TestCase
{
    private CustomerLedgerTimelineBuilder $builder;
    private Shop $shop;

    protected function setUp(): void
    {
        $this->builder = new CustomerLedgerTimelineBuilder();
        $this->shop = new Shop();
        $this->shop->setCurrency(CurrencyEnum::EURO);
    }

    /**
     * Une dette normale est affichée.
     */
    public function testDebtIsDisplayed(): void
    {
        $entry = $this->createDebt(10000);

        $timeline = $this->builder->build([$entry]);

        self::assertCount(1, $timeline);

        self::assertSame('DEBT', $timeline[0]->type);

        self::assertSame('ACTIVE', $timeline[0]->status);

        self::assertSame(new Money(10000)->decimal(), $timeline[0]->amount);

        self::assertFalse($timeline[0]->isCorrection);
    }

    /**
     * Une écriture de reverse n'est jamais affichée.
     */
    public function testReverseEntryIsHidden(): void
    {
        $debt = $this->createDebt(10000);

        $reverse = $debt->reverse();

        $timeline = $this->builder->build([
            $debt,
            $reverse,
        ]);

        self::assertCount(1, $timeline);

        self::assertSame(
            $debt->getUuid()->toRfc4122(),
            $timeline[0]->uuid,
        );
    }

    /**
     * Une dette annulée.
     */
    public function testCancelledDebt(): void
    {
        $debt = $this->createDebt(10000);

        $debt->reverse();

        $timeline = $this->builder->build([$debt]);

        self::assertSame(
            'CANCELLED',
            $timeline[0]->status,
        );
    }

    public function testCancelledPayment(): void
    {
        $payment = $this->createPayment(3000);

        $payment->reverse();

        $timeline = $this->builder->build([$payment]);

        self::assertSame(
            'CANCELLED',
            $timeline[0]->status,
        );
    }

    public function testCorrection(): void
    {
        $original = $this->createDebt(10000);

        $corrected = $this->createDebt(12000);

        $corrected->setCorrectedEntry($original);

        $timeline = $this->builder->build([
            $original,
            $corrected,
        ]);

        self::assertTrue(
            $timeline[1]->isCorrection,
        );

        self::assertSame(
            new Money(10000)->decimal(),
            $timeline[1]->previousAmount,
        );
    }

    public function testCancelledCannotBeReversed(): void
    {
        $entry = $this->createDebt(10000);

        $entry->reverse();

        $timeline = $this->builder->build([$entry]);

        self::assertFalse(
            $timeline[0]->canReverse,
        );
    }

    public function testCancelledCannotBeCorrected(): void
    {
        $entry = $this->createDebt(10000);

        $entry->reverse();

        $timeline = $this->builder->build([$entry]);

        self::assertFalse(
            $timeline[0]->canCorrect,
        );
    }

    public function testTimelineOrdering(): void
    {
        $debt = $this->createDebt(10000);

        $payment = $this->createPayment(5000);

        $timeline = $this->builder->build([
            $debt,
            $payment,
        ]);

        self::assertCount(2, $timeline);

        self::assertSame('DEBT', $timeline[0]->type);

        self::assertSame('PAYMENT', $timeline[1]->type);
    }

    private function createDebt(int $amount): LedgerEntry
    {
        $customer = new Customer();
        $customer->setShop($this->shop);

        return new LedgerEntry()
            ->setCustomer($customer)
            ->setShop($this->shop)
            ->setType(LedgerTypeEnum::DEBT)
            ->setAmountInCents($amount)
            ->setOccurredAt(new \DateTimeImmutable());
    }

    private function createPayment(int $amount): LedgerEntry
    {
        $customer = new Customer();
        $customer->setShop($this->shop);

        return new LedgerEntry()
            ->setCustomer($customer)
            ->setShop($this->shop)
            ->setType(LedgerTypeEnum::PAYMENT)
            ->setAmountInCents($amount)
            ->setOccurredAt(new \DateTimeImmutable());
    }
}
