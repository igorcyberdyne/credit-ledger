<?php

namespace App\Tests\Functional;

use App\Dto\Response\Infra\ApiResponse;
use App\Entity\Shop;
use App\Entity\User;
use App\Tests\Factory\ShopFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Tools\BasicWebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticatedApiTestCase extends BasicWebTestCase
{
    protected Shop $shop;
    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = ShopFactory::new()->createOneEntity();

        $this->user = UserFactory::new()
            ->setServices([
                UserPasswordHasherInterface::class => $this->getService(UserPasswordHasherInterface::class),
            ])
            ->createOneEntity([
                'shop' => $this->shop,
                'firstname' => $this->getGenerator()->firstName,
                'lastname' => $this->getGenerator()->lastName,
                'email' => sprintf('%s-%s', uniqid(), $this->getGenerator()->email()),
            ]);

        /** @var JWTTokenManagerInterface $jwt */
        $jwt = $this->getService(
            JWTTokenManagerInterface::class
        );

        $this->token = $jwt->create($this->user);
    }

    protected function authorizationHeaders(
        array $headers = [],
    ): array {
        return array_merge([
            'HTTP_AUTHORIZATION' => sprintf(
                'Bearer %s',
                $this->token,
            ),
        ], $headers);
    }

    protected function authenticatedGet(
        string $uri,
        array $query = [],
    ): ApiResponse {
        return parent::get(
            $uri,
            $query,
            $this->authorizationHeaders(),
        );
    }

    /**
     * @throws \Throwable
     */
    protected function authenticatedPost(
        string $uri,
        array $payload = [],
    ): ApiResponse {
        return parent::post(
            $uri,
            $payload,
            $this->authorizationHeaders(),
        );
    }

    /**
     * @throws \Throwable
     */
    protected function authenticatedPut(
        string $uri,
        array $payload = [],
    ): ApiResponse {
        return parent::put(
            $uri,
            $payload,
            $this->authorizationHeaders(),
        );
    }

    /**
     * @throws \Throwable
     */
    protected function authenticatedPatch(
        string $uri,
        array $payload = [],
    ): ApiResponse {
        return parent::patch(
            $uri,
            $payload,
            $this->authorizationHeaders(),
        );
    }

    /**
     * @throws \Throwable
     */
    protected function authenticatedDelete(
        string $uri,
    ): ApiResponse {
        return parent::delete(
            $uri,
            $this->authorizationHeaders(),
        );
    }
}
