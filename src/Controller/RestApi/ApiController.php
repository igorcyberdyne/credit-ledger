<?php

namespace App\Controller\RestApi;

use App\Dto\Response\ApiSuccessResponse;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends AbstractController
{
    protected function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user;
    }

    protected function apiSuccess(
        mixed $data = null,
        array $meta = [],
        string $message = '',
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return $this->json(new ApiSuccessResponse($data, $meta, $message ?? ''), $status);
    }

    protected function apiCreated(
        mixed $data = null,
    ): JsonResponse {
        return $this->apiSuccess(
            data: $data,
            status: Response::HTTP_CREATED,
        );
    }

    protected function noContent(): JsonResponse
    {
        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
