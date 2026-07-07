<?php

namespace App\Service\Security\Handler;

use App\Dto\Response\ApiSuccessResponse;
use App\Dto\Response\Security\LoginResponseDto;
use App\Dto\Response\Security\UserResponseDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private ObjectMapperInterface $objectMapper,
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
        $user = $token->getUser();

        $dto = new LoginResponseDto(
            $this->jwtManager->create($user),
            $this->objectMapper->map($user, UserResponseDto::class)
        );

        return new JsonResponse($this->serializer->serialize(new ApiSuccessResponse($dto), 'json', ['skip_null_values' => true]), json: true);
    }
}
