<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Dto\Response\Security\LoginResponse;
use App\Tests\Tools\BasicWebTestCase;
use App\Tests\Tools\Database;

final class OnboardingControllerTest extends BasicWebTestCase
{
    private const string URI = '/api/onboarding';

    public function setUp(): void
    {
        parent::setUp();

        Database::resetDatabase();
    }

    /**
     * @throws \Throwable
     */
    public function testShouldCreateAccount(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->post(
                self::URI,
                [
                    'shopName' => 'Chez Igor',
                    'address' => '10 rue Victor Hugo',
                    'postalCode' => '75001',
                    'city' => 'Paris',
                    'country' => 'France',
                    'shopPhone' => '+33102030405',
                    'currency' => 'EURO',
                    'timezone' => 'Europe/Paris',

                    'firstname' => 'Igor',
                    'lastname' => 'Gamath',
                    'email' => 'igor@test.fr',
                    'phone' => '+33650102030',
                    'password' => 'Password123!',
                ]
            );
            $this->assertCreated();
            $this->assertNotEmpty($response->apiSuccessResponse->data);

            $content = $response->apiSuccessResponse->data;
            /** @var LoginResponse $loginResponseDTO */
            $loginResponseDTO = $this->serializeJsonToDto($content, LoginResponse::class);
            $this->assertNotEmpty($loginResponseDTO->token);
            $this->assertNotEmpty($loginResponseDTO->refreshToken);
            $this->assertNotEmpty($loginResponseDTO->expiresIn);
            $this->assertNotEmpty($loginResponseDTO->userResponseDTO);
            $this->assertNotEmpty($loginResponseDTO->shopResponse);
            $this->assertEquals(3600, $loginResponseDTO->expiresIn);

            // Check serialization for api side client
            foreach (['token', 'refreshToken', 'user', 'shop'] as $key) {
                self::assertArrayHasKey($key, $content);
            }
        });
    }

    /**
     * @throws \Throwable
     */
    public function testShouldReturnManagerRole(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->post(
                self::URI,
                $this->validPayload()
            );

            $this->assertCreated();

            $content = $response->apiSuccessResponse->data;

            self::assertEquals(
                ['ROLE_MANAGER'],
                $content['user']['roles']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testShouldReturnShopInformation(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->post(
                self::URI,
                $this->validPayload()
            );

            $this->assertCreated();

            $content = $response->apiSuccessResponse->data;

            self::assertEquals(
                'Chez Igor',
                $content['shop']['name']
            );

            self::assertEquals(
                'Paris',
                $content['shop']['city']
            );

            self::assertEquals(
                'France',
                $content['shop']['country']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testShouldReturnJwt(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->post(
                self::URI,
                $this->validPayload()
            );

            $this->assertCreated();

            $content = $response->apiSuccessResponse->data;

            self::assertNotEmpty(
                $content['token']
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testShouldReturnRefreshToken(): void
    {
        $this->wrapInRollback(function () {
            $response = $this->post(
                self::URI,
                $this->validPayload()
            );

            $this->assertCreated();

            $content = $response->apiSuccessResponse->data;

            self::assertNotEmpty(
                $content['refreshToken']
            );
        });
    }

    private function validPayload(): array
    {
        return [
            'shopName' => 'Chez Igor',
            'address' => '10 rue Victor Hugo',
            'postalCode' => '75001',
            'city' => 'Paris',
            'country' => 'France',
            'shopPhone' => '+33102030405',
            'currency' => 'EURO',
            'timezone' => 'Europe/Paris',

            'firstname' => 'Igor',
            'lastname' => 'Gamath',
            'email' => 'igor@test.fr',
            'phone' => '+33650102030',
            'password' => 'Password123!',
        ];
    }
}
