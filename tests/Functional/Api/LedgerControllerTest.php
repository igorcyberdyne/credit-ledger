<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Dto\Response\Infra\ApiResponse;
use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Enum\PaymentMethodEnum;
use App\Repository\CustomerRepository;
use App\Repository\LedgerEntryRepository;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use App\Tests\Factory\CustomerFactory;
use App\Tests\Factory\LedgerEntryFactory;
use App\Tests\Factory\ShopFactory;
use App\Tests\Functional\AuthenticatedApiTestCase;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;

final class LedgerControllerTest extends AuthenticatedApiTestCase
{
    private CustomerRepository $customerRepository;
    private LedgerEntryRepository $ledgerRepository;
    private CustomerBalanceService $customerBalanceService;

    private function findLedgerEntry(string $uuid, ?Shop $shop = null): LedgerEntry
    {
        /* @var LedgerEntry $entry */
        if (null === $shop) {
            $entry = $this->ledgerRepository->findOneBy(['uuid' => $uuid]);
        } else {
            $entry = $this->ledgerRepository->findOneBy(['uuid' => $uuid, 'shop' => $shop]);
        }

        return $entry;
    }

    private function createCustomer(?Shop $shop = null): Customer
    {
        /** @var Customer $customer */
        $customer = CustomerFactory::new()
            ->with([
                'shop' => $shop ?? $this->shop,
            ])
            ->create();

        return $customer;
    }

    /**
     * @throws \Throwable
     */
    private function createDebt(
        Customer $customer,
        int $amountInCents,
        bool $returnLedgerEntry = false,
        ?callable $callback = null,
    ): array|LedgerEntry|null {
        $json = $this->authenticatedPost(
            sprintf(
                '/api/ledgers/customers/%s/debts',
                $customer->getUuid()->toRfc4122(),
            ),
            [
                'amountInCents' => $amountInCents,
                'description' => 'Courses alimentaires',
                'occurredAt' => '2026-07-13T10:00:00+00:00',
            ],
        );
        if ($callback) {
            $callback($json);
        } else {
            $this->assertCreated();
        }

        $uuid = $json->apiSuccessResponse->data['uuid'] ?? null;

        if ($returnLedgerEntry && $uuid) {
            return $this->findLedgerEntry($uuid);
        }

        return $json->apiSuccessResponse->data ?? null;
    }

    /**
     * @throws \Throwable
     */
    private function createPayment(
        Customer $customer,
        int $amountInCents,
        bool $returnLedgerEntry = false,
        ?callable $callback = null,
    ): array|LedgerEntry|null {
        $json = $this->authenticatedPost(
            sprintf(
                '/api/ledgers/customers/%s/payments',
                $customer->getUuid()->toRfc4122(),
            ),
            $params ??
            [
                'amountInCents' => $amountInCents,
                'paymentMethod' => 'CASH',
                'description' => 'Paiement comptant',
                'occurredAt' => '2026-07-13T10:00:00+00:00',
            ],
        );
        if ($callback) {
            $callback($json);
        } else {
            $this->assertCreated();
        }

        $uuid = $json->apiSuccessResponse->data['uuid'] ?? null;
        if ($returnLedgerEntry && $uuid) {
            return $this->findLedgerEntry($uuid);
        }

        return $json->apiSuccessResponse->data ?? null;
    }

    /**
     * @throws \Throwable
     */
    private function reverseLedgerEntry(
        string $ledgerUuid,
        ?callable $callback = null,
    ): ?array {
        $json = $this->authenticatedPost(
            sprintf(
                '/api/ledgers/%s/reverse',
                $ledgerUuid,
            ),
        );

        if (null !== $callback) {
            $return = $callback($json);

            return $return ?? null;
        }
        $this->assertOk();

        return $json->apiSuccessResponse->data;
    }

    /**
     * @throws \Throwable
     */
    private function correctLedgerEntry(
        string $ledgerUuid,
        array $payload,
        ?callable $callback = null,
    ): mixed {
        $response = $this->authenticatedPost(
            sprintf(
                '/api/ledgers/%s/correct',
                $ledgerUuid,
            ),
            $payload,
        );

        if (null !== $callback) {
            $return = $callback($response);

            return $return ?? null;
        }

        return $response;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = $this->getEntityManager()->getRepository(Customer::class);
        $this->ledgerRepository = $this->getEntityManager()->getRepository(LedgerEntry::class);
        $this->customerBalanceService = $this->getService(CustomerBalanceService::class);
    }

    /**
     * @throws \Throwable
     */
    public function testGetLedger(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer();

            /** @var LedgerEntry $entry */
            $entry = LedgerEntryFactory::new()
                ->debt()
                ->with([
                    'shop' => $this->shop,
                    'customer' => $customer,
                    'amountInCents' => 1590,
                    'description' => 'Description',
                    'occurredAt' => new \DateTimeImmutable('2026-07-13T12:14:30+02:00'),
                ])
                ->create();

            $response = $this->authenticatedGet(
                sprintf('/api/ledgers/%s', $entry->getUuid()->toRfc4122())
            );

            $this->assertOk();

            $this->assertEquals(
                [
                    'uuid' => $response->apiSuccessResponse->data['uuid'],
                    'type' => 'DEBT',
                    'description' => 'Description',
                    'amount' => '15.90',
                    'occurredAt' => '2026-07-13T12:14:30+02:00',
                ],
                $response->apiSuccessResponse->data
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testLedgerNotFound(): void
    {
        $this->authenticatedGet(
            '/api/ledgers/01999999-9999-7999-9999-999999999999'
        );

        $this->assertNotFound();
    }

    /**
     * @throws \Throwable
     */
    public function testCannotGetLedgerForAnotherShop(): void
    {
        $this->wrapInRollback(function (): void {
            $otherShop = ShopFactory::new()->create();

            $customer = $this->createCustomer($otherShop);

            /** @var LedgerEntry $entry */
            $entry = LedgerEntryFactory::new()
                ->debt()
                ->with([
                    'shop' => $otherShop,
                    'customer' => $customer,
                    'amountInCents' => 1200,
                ])
                ->create();

            $this->authenticatedGet(
                sprintf('/api/ledgers/%s', $entry->getUuid()->toRfc4122())
            );

            $this->assertNotFound();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCannotCreateDebtForAnotherShopCustomer(): void
    {
        $this->wrapInRollback(function (): void {
            $otherShop = ShopFactory::new()
                ->create();

            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $otherShop,
                ])
                ->create();

            $this->createDebt(
                $customer,
                1000,
                true,
                function (): void {
                    $this->assertNotFound();
                }
            );
        });
    }

    public function testCustomerNotFound(): void
    {
        $this->authenticatedGet(
            '/api/ledgers/customers/0197b85c-1f1d-7f41-a111-aaaaaaaaaaaa/ledger'
        );

        $this->assertNotFound();
    }

    public function testGetCustomerLedger(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            LedgerEntryFactory::new()
                ->debt()
                ->with([
                    'shop' => $this->shop,
                    'customer' => $customer,
                    'amountInCents' => 1000,
                ])
                ->create();

            LedgerEntryFactory::new()
                ->payment()
                ->with([
                    'shop' => $this->shop,
                    'customer' => $customer,
                    'amountInCents' => 300,
                    'paymentMethod' => PaymentMethodEnum::CASH,
                ])
                ->create();

            $json = $this->authenticatedGet(
                sprintf(
                    '/api/ledgers/customers/%s/ledger',
                    $customer->getUuid(),
                )
            )->apiSuccessResponse->data;

            $this->assertOk();

            self::assertArrayHasKey('entries', $json);
            self::assertCount(2, $json['entries']);
        });
    }

    public function testGetCustomerLedgerWithPagination(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            LedgerEntryFactory::new()
                ->debt()
                ->createManyEntities(
                    25,
                    [
                        'shop' => $this->shop,
                        'customer' => $customer,
                    ]);

            $json = $this->authenticatedGet(
                sprintf(
                    '/api/ledgers/customers/%s/ledger?page=2&limit=10',
                    $customer->getUuid(),
                )
            )->apiSuccessResponse->data;

            $this->assertOk();

            self::assertCount(
                10,
                $json['entries'],
            );

            self::assertSame(
                25,
                $json['pagination']['total'],
            );

            self::assertSame(
                2,
                $json['pagination']['page'],
            );
        });
    }

    // ---------------------------------
    // DEBT
    // ---------------------------------

    /**
     * @throws \Throwable
     */
    public function testCreateDebt(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            $json = $this->createDebt($customer, 2500);

            self::assertSame(
                new Money(2500)->decimal(),
                $json['amount'],
            );
            self::assertSame(
                '25.00',
                $json['amount'],
            );

            self::assertSame(
                'DEBT',
                $json['type'],
            );

            self::assertSame(
                'Courses alimentaires',
                $json['description'],
            );
        });
    }

    // ---------------------------------
    // PAYMENT
    // ---------------------------------

    /**
     * @throws \Throwable
     */
    public function testCreatePayment(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            $this->createDebt($customer, 5000);

            $json = $this->createPayment($customer, 2000);

            self::assertSame(
                'PAYMENT',
                $json['type'],
            );

            self::assertSame(
                '20.00',
                $json['amount'],
            );

            self::assertSame(
                'CASH',
                $json['paymentMethod'],
            );
        });
    }

    // ---------------------------------
    // REVERSE
    // ---------------------------------

    /**
     * @throws \Throwable
     */
    public function testReverseDebt(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            $json = $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/customers/%s/debts',
                    $customer->getUuid(),
                ),
                [
                    'amountInCents' => 1500,
                    'description' => 'Courses alimentaires',
                    'occurredAt' => '2026-07-13T10:00:00+00:00',
                ],
            )->apiSuccessResponse->data;
            $this->assertCreated();

            $entry = $this->findLedgerEntry($json['uuid'], $this->shop);
            $response = $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/%s/reverse',
                    $entry->getUuid()->toRfc4122(),
                ),
            )->apiSuccessResponse->data;
            $this->assertOk();

            self::assertSame(
                'PAYMENT',
                $response['type'],
            );

            self::assertSame(
                '15.00',
                $response['amount'],
            );

            // ---------------------------------
            // Vérification en base
            // ---------------------------------

            $entityManager->clear();

            /** @var ?LedgerEntry $original */
            $original = $this->ledgerRepository
                ->findOneBy(
                    ['uuid' => $entry->getUuid()->toRfc4122()],
                );

            self::assertNotNull($original);

            self::assertSame(
                $entry->getUuid()->toRfc4122(),
                $original->getUuid()->toRfc4122(),
            );

            self::assertTrue(
                $original->isReversed(),
            );

            $reversal = $original->getReversal();
            self::assertNotNull(
                $reversal,
            );
            self::assertSame(
                $reversal->getUuid()->toRfc4122(),
                $response['uuid'],
            );

            self::assertEquals(
                LedgerTypeEnum::PAYMENT,
                $reversal->getType(),
            );

            // ---------------------------------
            // Balance client
            // ---------------------------------
            /** @var Customer $customer */
            $customer = $this->customerRepository->find($customer->getId());

            self::assertSame(
                0,
                $this->customerBalanceService->getBalanceInCents($customer),
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testReversePayment(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            /** @var Customer $customer */
            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $this->shop,
                ])
                ->create();

            $this->createDebt($customer, 500);
            /** @var Customer $customer */
            $customer = $this->customerRepository->find($customer->getId());
            self::assertSame(
                500,
                $this->customerBalanceService->getBalanceInCents($customer),
            );

            $entry = $this->createPayment($customer, 500, true);

            // Reverse payment
            $response = $this->reverseLedgerEntry($entry->getUuid()->toRfc4122());

            self::assertSame(
                'DEBT',
                $response['type'],
            );

            // ---------------------------------
            // Vérification en base
            // ---------------------------------

            $entityManager->clear();

            /** @var ?LedgerEntry $payment */
            $payment = $this
                ->ledgerRepository
                ->findOneBy([
                    'uuid' => $entry->getUuid()->toRfc4122(),
                ])
            ;

            self::assertTrue($payment->isReversed());
            self::assertNotNull($payment->getReversal());

            /** @var Customer $customer */
            $customer = $this->customerRepository->find($customer->getId());
            self::assertSame(
                500,
                $this->customerBalanceService->getBalanceInCents($customer),
            );
        });
    }

    public function testCannotReverseAlreadyReversedEntry(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            $customer = $this->createCustomer();

            $entry = $this->createDebt(
                $customer,
                1000,
                returnLedgerEntry: true
            );

            $this->reverseLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                function (): void {
                    $this->assertOk();
                }
            );

            // ---------------------------------
            // deuxième reverse
            // ---------------------------------

            $this->reverseLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                function (): void {
                    $this->assertConflict();
                }
            );

            // ---------------------------------
            // Une seule reversal
            // ---------------------------------

            $entityManager->clear();

            $entry = $this->findLedgerEntry($entry->getUuid()->toRfc4122());

            self::assertNotNull($entry->getReversal());

            self::assertNull($entry->getReversal()->getReversal());
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCannotCreateDebtOrPaymentToAnotherShop(): void
    {
        $this->wrapInRollback(function (): void {
            $otherShop = ShopFactory::new()->create();

            $customer = $this->createCustomer($otherShop);

            $this->createDebt(
                $customer,
                1200,
                callback: function () {
                    $this->assertNotFound();
                }
            );

            $this->createPayment(
                $customer,
                1200,
                callback: function () {
                    $this->assertNotFound();
                }
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCannotReverseAnotherShopEntry(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            $otherShop = ShopFactory::new()->create();

            $customer = $this->createCustomer($otherShop);

            /** @var LedgerEntry $entry */
            $entry = LedgerEntryFactory::new()
                ->debt()
                ->with([
                    'shop' => $otherShop,
                    'customer' => $customer,
                    'amountInCents' => 1200,
                ])
                ->create();

            // The shop request in this request is related to the user connected by token. The user for $this->shop
            $this->reverseLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                function (): void {
                    $this->assertNotFound();
                }
            );

            $entityManager->clear();

            $entry = $this->findLedgerEntry($entry->getUuid()->toRfc4122());

            self::assertFalse($entry->isReversed());

            self::assertNull($entry->getReversal());
        });
    }

    // ---------------------------------
    // CORRECT
    // ---------------------------------

    public function testCorrectDebt(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            $customer = $this->createCustomer();

            $entry = $this->createDebt(
                $customer,
                1000,
                true,
                function (): void {
                    $this->assertCreated();
                }
            );

            $response = $this->correctLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                [
                    'amountInCents' => 1800,
                    'paymentMethod' => 'CASH',
                ],
                function (ApiResponse $response): array {
                    $this->assertOk();

                    return $response->apiSuccessResponse->data;
                }
            );

            self::assertSame(
                'DEBT',
                $response['type'],
            );

            self::assertSame(
                '18.00',
                $response['amount'],
            );

            $entityManager->clear();

            $original = $this->findLedgerEntry($entry->getUuid()->toRfc4122());

            self::assertTrue($original->isReversed());

            self::assertNotNull($original->getReversal());

            /** @var Customer $customer */
            $customer = $this->customerRepository->find($customer->getId());

            self::assertSame(
                1800,
                $this->customerBalanceService->getBalanceInCents($customer),
            );

            self::assertCount(
                3,
                $customer->getLedgerEntries()
            );
        });
    }

    public function testCorrectPayment(): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager): void {
            $customer = $this->createCustomer();

            $this->createDebt(
                $customer,
                2000,
                true
            );

            $payment = $this->createPayment(
                $customer,
                500,
                true
            );

            $response = $this->correctLedgerEntry(
                $payment->getUuid()->toRfc4122(),
                [
                    'amountInCents' => 800,
                    'paymentMethod' => 'CARD',
                    // 'description' => 'Paiement corrigé',
                    'occurredAt' => '2026-07-13T10:00:00+00:00',
                ],
                function (ApiResponse $response): array {
                    $this->assertOk();

                    return $response->apiSuccessResponse->data;
                }
            );

            self::assertSame(
                'PAYMENT',
                $response['type'],
            );

            self::assertSame(
                '8.00',
                $response['amount'],
            );

            self::assertSame(
                'CARD',
                $response['paymentMethod'],
            );

            $entityManager->clear();

            $payment = $this->findLedgerEntry($payment->getUuid()->toRfc4122());

            self::assertTrue(
                $payment->isReversed(),
            );

            /** @var Customer $customer */
            $customer = $this->customerRepository->find($customer->getId());

            self::assertSame(
                1200,
                $this->customerBalanceService->getBalanceInCents($customer),
            );
            self::assertCount(
                4,
                $customer->getLedgerEntries()
            );
        });
    }

    public function testCannotCorrectAlreadyReversedEntry(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer();

            $entry = $this->createDebt(
                $customer,
                1000,
                true
            );

            $this->reverseLedgerEntry($entry->getUuid()->toRfc4122());
            $this->assertOk();

            $this->correctLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                [
                    'amountInCents' => 1500,
                    'description' => 'Impossible',
                    'occurredAt' => '2026-07-13T10:00:00+00:00',
                ],
            );
            $this->assertConflict();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCannotCorrectAnotherShopEntry(): void
    {
        $this->wrapInRollback(function (): void {
            $otherShop = ShopFactory::new()
                ->create();

            $customer = CustomerFactory::new()
                ->with([
                    'shop' => $otherShop,
                ])
                ->create();

            /** @var LedgerEntry $entry */
            $entry = LedgerEntryFactory::new()
                ->debt()
                ->with([
                    'shop' => $otherShop,
                    'customer' => $customer,
                    'amountInCents' => 1000,
                ])
                ->create();

            $this->correctLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                [
                    'amountInCents' => 1500,
                    'description' => 'Hack',
                    'occurredAt' => '2026-07-13T10:00:00+00:00',
                ],
            );

            $this->assertNotFound();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testValidationCorrectEntry(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer();

            $entry = $this->createDebt(
                $customer,
                1000,
                true
            );

            $this->correctLedgerEntry(
                $entry->getUuid()->toRfc4122(),
                [
                    'amountInCents' => -500,
                ],
            );

            $this->assertValidationError();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCreateDebtValidationAmountIsRequired(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer();

            $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/customers/%s/debts',
                    $customer->getUuid()->toRfc4122(),
                ),
                [
                    'description' => 'Courses alimentaires',
                ],
            );

            $this->assertValidationError();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCreateDebtValidationAmountMustBePositive(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer();

            $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/customers/%s/debts',
                    $customer->getUuid(),
                ),
                [
                    'amountInCents' => -100,
                    'description' => 'Courses',
                ],
            );

            $this->assertValidationError();
        });
    }

    public function testCreatePaymentValidationPaymentMethod(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer($this->shop);

            $this->createDebt(
                $customer,
                1000,
                true
            );

            $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/customers/%s/payments',
                    $customer->getUuid(),
                ),
                [
                    'amountInCents' => 500,
                    'paymentMethod' => 'BITCOIN',
                ],
            );

            $this->assertValidationError();
        });
    }

    public function testCreatePaymentValidationAmount(): void
    {
        $this->wrapInRollback(function (): void {
            $customer = $this->createCustomer($this->shop);

            $this->createDebt(
                $customer,
                1000,
                true
            );

            $this->authenticatedPost(
                sprintf(
                    '/api/ledgers/customers/%s/payments',
                    $customer->getUuid(),
                ),
                [
                    'amountInCents' => 0,
                    'paymentMethod' => 'CASH',
                ],
            );

            $this->assertValidationError();
        });
    }
}
