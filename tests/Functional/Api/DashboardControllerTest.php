<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Factory\CustomerFactory;
use App\Tests\Factory\LedgerEntryFactory;
use App\Tests\Factory\ShopFactory;
use App\Tests\Functional\AuthenticatedApiTestCase;

final class DashboardControllerTest extends AuthenticatedApiTestCase
{
    public function testDashboardIsEmpty(): void
    {
        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(0, $json['customers']);
        self::assertSame(0, $json['customersWithDebt']);
        self::assertSame(0, $json['ledgerEntries']);
        self::assertSame(0, $json['debts']);
        self::assertSame(0, $json['payments']);
        self::assertSame(0, $json['totalDebtInCents']);
        self::assertSame(0, $json['todayDebtInCents']);
        self::assertSame(0, $json['todayPaymentsInCents']);
    }

    public function testDashboardWithCustomers(): void
    {
        CustomerFactory::new()
            ->createManyEntities(
                5,
                [
                    'shop' => $this->shop,
                ]);

        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(5, $json['customers']);
        self::assertSame(0, $json['customersWithDebt']);
    }

    public function testDashboardWithDebts(): void
    {
        $customer = CustomerFactory::new()
            ->createOneEntity([
                'shop' => $this->shop,
            ]);

        LedgerEntryFactory::new()
            ->debt()
            ->createOneEntity([
                'shop' => $this->shop,
                'customer' => $customer,
                'amountInCents' => 1000,
            ]);

        LedgerEntryFactory::new()
            ->debt()
            ->createOneEntity([
                'shop' => $this->shop,
                'customer' => $customer,
                'amountInCents' => 2000,
            ]);

        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(1, $json['customers']);
        self::assertSame(1, $json['customersWithDebt']);
        self::assertSame(2, $json['debts']);
        self::assertSame(2, $json['ledgerEntries']);
        self::assertSame(3000, $json['totalDebtInCents']);
    }

    public function testDashboardWithPayments(): void
    {
        $this->wrapInRollback(function () {
            $customer = CustomerFactory::new()
                ->createOneEntity([
                    'shop' => $this->shop,
                ]);

            LedgerEntryFactory::new()
                ->debt()
                ->createOneEntity([
                    'shop' => $this->shop,
                    'customer' => $customer,
                    'amountInCents' => 1000,
                ]);

            LedgerEntryFactory::new()
                ->payment()
                ->createOneEntity([
                    'shop' => $this->shop,
                    'customer' => $customer,
                    'amountInCents' => 500,
                ]);

            $json = $this->authenticatedGet('/api/dashboard');
            $this->assertOk();

            $json = $json->apiSuccessResponse->data;

            self::assertSame(2, $json['ledgerEntries']);
            self::assertSame(1, $json['debts']);
            self::assertSame(1, $json['payments']);
            self::assertSame(500, $json['totalDebtInCents']);
        });
    }

    public function testDashboardTodayStatistics(): void
    {
        $customer = CustomerFactory::new()
            ->createOneEntity([
                'shop' => $this->shop,
            ]);

        LedgerEntryFactory::new()
            ->debt()
            ->createOneEntity([
                'shop' => $this->shop,
                'customer' => $customer,
                'amountInCents' => 1000,
                'occurredAt' => new \DateTimeImmutable(),
            ]);

        LedgerEntryFactory::new()
            ->payment()
            ->createOneEntity([
                'shop' => $this->shop,
                'customer' => $customer,
                'amountInCents' => 400,
                'occurredAt' => new \DateTimeImmutable(),
            ]);

        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(1000, $json['todayDebtInCents']);
        self::assertSame(400, $json['todayPaymentsInCents']);
    }

    public function testDashboardIgnoresOtherShop(): void
    {
        $otherShop = ShopFactory::new()->create();

        $customer = CustomerFactory::new()
            ->with([
                'shop' => $otherShop,
            ])
            ->create();

        LedgerEntryFactory::new()
            ->debt()
            ->createOneEntity([
                'shop' => $this->shop,
                'customer' => $customer,
                'amountInCents' => 5000,
            ]);

        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(0, $json['customers']);
        self::assertSame(0, $json['customersWithDebt']);
        self::assertSame(0, $json['ledgerEntries']);
        self::assertSame(0, $json['debts']);
        self::assertSame(0, $json['payments']);
        self::assertSame(0, $json['totalDebtInCents']);
    }

    public function testDashboardAggregatesMultipleCustomers(): void
    {
        $customer1 = CustomerFactory::new()
            ->with([
                'shop' => $this->shop,
            ])
            ->create();

        $customer2 = CustomerFactory::new()
            ->with([
                'shop' => $this->shop,
            ])
            ->create();

        LedgerEntryFactory::new()
            ->debt()
            ->with([
                'shop' => $this->shop,
                'customer' => $customer1,
                'amountInCents' => 1000,
            ])
            ->create();

        LedgerEntryFactory::new()
            ->debt()
            ->with([
                'shop' => $this->shop,
                'customer' => $customer2,
                'amountInCents' => 2000,
            ])
            ->create();

        $json = $this->authenticatedGet('/api/dashboard');
        $this->assertOk();

        $json = $json->apiSuccessResponse->data;

        self::assertSame(2, $json['customers']);
        self::assertSame(2, $json['customersWithDebt']);
        self::assertSame(3000, $json['totalDebtInCents']);
        self::assertSame(2, $json['debts']);
    }
}
