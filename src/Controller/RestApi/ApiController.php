<?php

namespace App\Controller\RestApi;

use App\Dto\Response\ApiSuccessResponse;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    protected function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user;
    }

    protected function apiResponse(mixed $data, ?string $message = null): JsonResponse
    {
        return parent::json(new ApiSuccessResponse($data, $message ?? ''));
    }
}
