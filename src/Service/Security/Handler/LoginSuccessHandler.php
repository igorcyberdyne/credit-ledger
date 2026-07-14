<?php

namespace App\Service\Security\Handler;

use App\Dto\Response\Infra\ApiSuccessResponse;
use App\Dto\Response\Security\LoginResponse;
use App\Entity\User;
use App\Mapper\UserMapper;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
    ): ?Response {
        /** @var User $user */
        $user = $token->getUser();

        $dto = new LoginResponse(
            $this->jwtManager->create($user),
            UserMapper::toResponse($user)
        );

        return new JsonResponse(
            $this->serializer->serialize(
                new ApiSuccessResponse($dto),
                'json',
                ['skip_null_values' => true]
            ),
            json: true
        );
    }
}
