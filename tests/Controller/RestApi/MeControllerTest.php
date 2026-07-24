<?php

namespace App\Tests\Controller\RestApi;

use App\Dto\Response\Infra\ApiErrorResponse;
use App\Dto\Response\Security\UserResponse;
use App\Entity\Shop;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\Security\Provider\SystemUserProvider;
use App\Tests\Tools\BasicWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class MeControllerTest extends BasicWebTestCase
{
    private function givenUser(EntityManagerInterface $entityManager): User
    {
        $user = $this->createUser(
            $shop ?? $entityManager->getRepository(Shop::class)->findAll()[0],
            $this->getGenerator()->unique()->email(),
            [UserRoleEnum::EMPLOYEE->value],
        );

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * @throws ExceptionInterface
     */
    private function authenticateUserAndRetrieveUserDto(string $token): UserResponse
    {
        $this->kernelBrowser->request(
            'GET',
            $this->generateUrl('api_me'),
            server: [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = json_decode($this->kernelBrowser->getResponse()->getContent(), true)['data'] ?? null;
        $userDto = $this->serializeJsonToDto($content, UserResponse::class);
        $this->assertInstanceOf(UserResponse::class, $userDto);

        return $userDto;
    }

    /**
     * @throws \Throwable
     */
    public function testMeUserSystem(): void
    {
        $this->wrapInRollback(function () {
            $loginResponseDTO = $this->fullAuthenticateUser(SystemUserProvider::USER_SYSTEM_EMAIL, [UserRoleEnum::MANAGER->value]);

            $this->assertEquals(
                new UserResponse(
                    uuid: $loginResponseDTO->userResponseDTO->uuid,
                    email: 'credit-ledger-app@system.com',
                    firstName: 'Gogo',
                    lastName: 'GAMATH',
                    roles: ['ROLE_SYSTEM'],
                ),
                $loginResponseDTO->userResponseDTO
            );

            $userDto = $this->authenticateUserAndRetrieveUserDto($loginResponseDTO->token);
            $this->assertEquals(
                $loginResponseDTO->userResponseDTO,
                $userDto
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testMe(): void
    {
        $this->wrapInRollback(function () {
            $loginResponseDTO = $this->fullAuthenticateUser('me@test.com', [UserRoleEnum::MANAGER->value]);

            $userDto = $this->authenticateUserAndRetrieveUserDto($loginResponseDTO->token);
            $this->assertEquals($loginResponseDTO->userResponseDTO->email, $userDto->email);
        });
    }

    public static function errorDataProvider(): array
    {
        return [
            'TOKEN_EXPIRED' => [
                'expectedCode' => 'TOKEN_EXPIRED',
                'expectedMessage' => 'Votre session a expiré. Veuillez vous reconnecter.',
            ],
            'INVALID_TOKEN' => [
                'expectedCode' => 'INVALID_TOKEN',
                'expectedMessage' => 'Le token fourni est invalide.',
            ],
            'TOKEN_MISSING' => [
                'expectedCode' => 'TOKEN_MISSING',
                'expectedMessage' => 'Aucun token d’authentification n’a été fourni.',
            ],
        ];
    }

    /**
     * @throws \Throwable
     */
    #[DataProvider(methodName: 'errorDataProvider')]
    public function testFailureAuthentication(string $expectedCode, string $expectedMessage): void
    {
        $this->wrapInRollback(function (EntityManagerInterface $entityManager) use ($expectedCode, $expectedMessage) {
            /** @var JWTTokenManagerInterface $jwt */
            $jwt = $this->getService(
                JWTTokenManagerInterface::class
            );

            $token = call_user_func(match ($expectedCode) {
                'TOKEN_EXPIRED' => function () use ($jwt, $entityManager) {
                    $user = $this->givenUser($entityManager);

                    return $jwt->createFromPayload($user, [
                        'exp' => time() - 60,
                    ]);
                },
                'INVALID_TOKEN' => function () {
                    return 'invalid-token';
                },
                default => function () {
                    return '';
                },
            });

            $this->kernelBrowser->request(
                'GET',
                $this->generateUrl('api_me'),
                server: [
                    'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                ]
            );

            $this->assertUnauthorized();
            $this->assertResponseFormatSame('json');

            $content = json_decode($this->kernelBrowser->getResponse()->getContent(), true)['error'] ?? null;
            $apiResponse = $this->serializeJsonToDto($content, ApiErrorResponse::class);
            $this->assertInstanceOf(ApiErrorResponse::class, $apiResponse);

            $this->assertEquals($expectedCode, $apiResponse->code);
            $this->assertEquals($expectedMessage, $apiResponse->message);
        });
    }
}
