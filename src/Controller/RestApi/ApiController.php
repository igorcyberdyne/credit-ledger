<?php

namespace App\Controller\RestApi;

use App\Dto\Response\Infra\ApiSuccessResponse;
use App\Entity\Shop;
use App\Entity\User;
use App\Exception\Domain\User\UserDoesNotHaveShopException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class ApiController extends AbstractController
{
    protected function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        return $user;
    }

    protected function getShop(): Shop
    {
        $shop = $this->getAuthenticatedUser()->getShop();

        if (!$shop instanceof Shop) {
            throw new UserDoesNotHaveShopException();
        }

        return $shop;
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
