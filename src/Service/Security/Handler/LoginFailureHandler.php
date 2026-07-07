<?php

namespace App\Service\Security\Handler;

use App\Dto\Response\ApiErrorResponse;
use App\Dto\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = new ApiResponse(apiErrorResponse: new ApiErrorResponse('INVALID_CREDENTIALS', 'Invalid credentials'));

        return new JsonResponse($this->serializer->serialize($data, 'json', ['skip_null_values' => true]), 401, json: true);
    }
}
