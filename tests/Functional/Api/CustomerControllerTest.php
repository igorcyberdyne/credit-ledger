<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Customer;
use App\Enum\CustomerStatusEnum;
use App\Tests\Factory\CustomerFactory;
use App\Tests\Functional\AuthenticatedApiTestCase;

final class CustomerControllerTest extends AuthenticatedApiTestCase
{
    /**
     * @throws \Throwable
     */
    public function testListCustomers(): void
    {
        $this->wrapInRollback(function () {
            CustomerFactory::new()->createManyEntities(
                10,
                [
                    'shop' => $this->shop,
                ]
            );

            // Route name : api_customer_index
            $response = $this->authenticatedGet('/api/customers');

            $this->assertOk();

            self::assertNotNull($response->apiSuccessResponse->data);

            self::assertCount(
                10,
                $response->apiSuccessResponse->data['customers']
            );
        });
    }

    public function testGetCustomer(): void
    {
        $this->wrapInRollback(function () {
            $customer = CustomerFactory::new()
                ->createOneEntity([
                    'shop' => $this->shop,
                ]);

            $response = $this->authenticatedGet(
                sprintf('/api/customers/%s', $customer->getUuid())
            );

            $this->assertOk();

            self::assertEquals(
                $customer->getUuid()->toRfc4122(),
                $response->apiSuccessResponse->data['uuid']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCreateCustomer(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->authenticatedPost(
                '/api/customers',
                [
                    'firstname' => 'Jean',
                    'lastname' => 'Dupont',
                    'phone' => '0600000000',
                ]
            );

            $this->assertCreated();

            self::assertEquals(
                'Jean',
                $response->apiSuccessResponse->data['firstname']
            );

            self::assertEquals(
                0,
                $response->apiSuccessResponse->data['balanceInCents']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testUpdateCustomer(): void
    {
        $this->wrapInRollback(function () {
            $customer = CustomerFactory::new()
                ->createOneEntity([
                    'shop' => $this->shop,
                ]);

            $response = $this->authenticatedPut(
                sprintf('/api/customers/%s', $customer->getUuid()),
                [
                    'firstname' => 'Paul',
                    'lastname' => 'Martin',
                    'phone' => '0700000000',
                ]
            );

            $this->assertOk();

            self::assertEquals(
                'Paul',
                $response->apiSuccessResponse->data['firstname']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testArchiveCustomer(): void
    {
        $this->wrapInRollback(function () {
            /** @var Customer $customer */
            $customer = CustomerFactory::new()
                ->createOneEntity([
                    'shop' => $this->shop,
                ]);

            $this->assertTrue(CustomerStatusEnum::ARCHIVED !== $customer->getStatus());
            $this->authenticatedPatch(
                sprintf('/api/customers/%s/archive', $customer->getUuid())
            );

            $this->assertNoContent();

            $customer = $this->getEntityManager()->getRepository(Customer::class)->findOneBy(['uuid' => $customer->getUuid()]);
            $this->assertTrue(CustomerStatusEnum::ARCHIVED === $customer->getStatus());
        });
    }

    public function testCannotAccessCustomerFromAnotherShop(): void
    {
        $this->wrapInRollback(function () {
            $customer = CustomerFactory::new()->createOneEntity();

            $this->authenticatedGet(
                sprintf('/api/customers/%s', $customer->getUuid())
            );

            $this->assertNotFound();
        });
    }

    /**
     * @throws \Throwable
     */
    public function testCreateCustomerValidation(): void
    {
        $this->authenticatedPost(
            '/api/customers',
        );

        $this->assertValidationError();
    }

    public function testCustomerNotFound(): void
    {
        $this->authenticatedGet(
            '/api/customers/01999999-9999-7999-9999-999999999999'
        );

        $this->assertNotFound();
    }

    /**
     * @throws \Throwable
     */
    public function testDuplicatePhone(): void
    {
        $this->wrapInRollback(function () {
            CustomerFactory::new()
                ->createOneEntity([
                    'shop' => $this->shop,
                    'phone' => '0600000000',
                ]);

            $this->authenticatedPost(
                '/api/customers',
                [
                    'firstname' => 'Jean',
                    'lastname' => 'Dupont',
                    'phone' => '0600000000',
                ]
            );

            $this->assertConflict();
        });
    }

    public function testPagination(): void
    {
        $this->wrapInRollback(function () {
            CustomerFactory::new()->createManyEntities(
                35,
                [
                    'shop' => $this->shop,
                ]
            );

            $response = $this->authenticatedGet(
                '/api/customers?page=2&limit=10',
            );

            $this->assertOk();
            self::assertCount(
                10,
                $response->apiSuccessResponse->data['customers']
            );
            self::assertEquals(
                [
                    'nextUri' => 'http://localhost:8080/api/customers?page=3&limit=10',
                    'previousUri' => 'http://localhost:8080/api/customers?page=1&limit=10',
                    'page' => 2,
                    'limit' => 10,
                    'total' => 35,
                    'pages' => 4,
                ],
                $response->apiSuccessResponse->data['pagination']
            );
        });
    }
}
