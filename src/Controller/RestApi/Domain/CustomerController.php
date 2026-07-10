<?php

namespace App\Controller\RestApi\Domain;

use App\Controller\RestApi\ApiController;
use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Service\Domain\Customer\Contracts\CustomerServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customers', name: 'customer_')]
final class CustomerController extends ApiController
{
    public function __construct(
        private readonly CustomerServiceInterface $customerService,
    ) {
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(
        string $uuid,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->customerService->getCustomer(
                $this->getShop(),
                $uuid
            )
        );
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateCustomerCommand $command,
    ): JsonResponse {
        return $this->apiCreated(
            $this->customerService->create(
                $this->getShop(),
                $command
            )
        );
    }

    #[Route('/{uuid}', name: 'update', methods: ['PUT'])]
    public function update(
        string $uuid,
        #[MapRequestPayload] UpdateCustomerCommand $command,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->customerService->update(
                $command,
                $uuid
            )
        );
    }

    #[Route('/{uuid}', name: 'archive', methods: ['PATCH'])]
    public function archive(string $uuid): JsonResponse
    {
        $this->customerService->archive($uuid);

        return $this->apiSuccess(
            status: Response::HTTP_NO_CONTENT
        );
    }
}
