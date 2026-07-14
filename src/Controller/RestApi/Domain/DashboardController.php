<?php

namespace App\Controller\RestApi\Domain;

use App\Controller\RestApi\ApiController;
use App\Service\Domain\GetDashboardService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'dashboard_')]
final class DashboardController extends ApiController
{
    public function __construct(
        private readonly GetDashboardService $getDashboardService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->apiSuccess(
            $this->getDashboardService->get(
                $this->getShop(),
            )
        );
    }
}
