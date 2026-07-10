<?php

namespace App\Tests\Controller\RestApi;

use App\Dto\Response\Security\UserResponse;
use App\Enum\UserRoleEnum;
use App\Service\Security\Provider\SystemUserProvider;
use App\Tests\Tools\BasicWebTestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class MeControllerTest extends BasicWebTestCase
{
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
            $loginResponseDTO = $this->fullAuthenticateUser(SystemUserProvider::USER_SYSTEM_EMAIL, [UserRoleEnum::OWNER->value]);

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
            $loginResponseDTO = $this->fullAuthenticateUser('me@test.com', [UserRoleEnum::OWNER->value]);

            $userDto = $this->authenticateUserAndRetrieveUserDto($loginResponseDTO->token);
            $this->assertEquals($loginResponseDTO->userResponseDTO->email, $userDto->email);
        });
    }
}
