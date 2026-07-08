<?php

namespace App\Controller\RestApi;

use App\Mapper\UserMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MeController extends ApiController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->apiSuccess(UserMapper::toResponse($this->getAuthenticatedUser()));
    }
}
