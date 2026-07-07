<?php

namespace App\Controller\RestApi;

use App\Dto\Response\Security\UserResponseDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;

class MeController extends ApiController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function __invoke(ObjectMapperInterface $objectMapper): JsonResponse
    {
        return $this->apiResponse($objectMapper->map($this->getAuthenticatedUser(), UserResponseDto::class));
    }
}
