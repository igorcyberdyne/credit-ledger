<?php

namespace App\Controller\RestApi\Domain;

use App\Controller\RestApi\ApiController;
use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Dto\Command\Customer\UpdateCustomerCommand;
use App\Dto\Criteria\Customer\PaginationCriteria;
use App\Service\Domain\Customer\Contracts\CustomerServiceInterface;
use App\Service\Domain\Ledger\GetCustomersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
#[Route('/customers', name: 'customers_')]
final class CustomerController extends ApiController
{
    public function __construct(
        private readonly CustomerServiceInterface $customerService,
        private readonly GetCustomersService $getCustomersService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        PaginationCriteria $pagination,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->getCustomersService->list(
                $this->getShop(),
                $pagination,
                $this->generateUrl(
                    'api_customers_index',
                    referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                ),
            )
        );
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

    #[Route('', name: 'create', methods: ['POST'])]
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

    #[Route('/{uuid}/archive', name: 'archive', methods: ['PATCH'])]
    public function archive(string $uuid): JsonResponse
    {
        $this->customerService->archive($uuid);

        return $this->apiSuccess(
            status: Response::HTTP_NO_CONTENT
        );
    }
}
