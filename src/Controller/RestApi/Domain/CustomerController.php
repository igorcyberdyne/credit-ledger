<?php

namespace App\Controller\RestApi\Domain;

use App\Controller\RestApi\ApiController;
use App\Dto\Command\Domain\Customer\CreateCustomerCommand;
use App\Dto\Command\Domain\Customer\UpdateCustomerCommand;
use App\Dto\Criteria\Customer\PaginationCriteria;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\Domain\Customer\Contracts\CustomerServiceInterface;
use App\Service\Domain\Ledger\GetCustomersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_EMPLOYEE')]
#[Route('/customers', name: 'customers_')]
final class CustomerController extends ApiController
{
    public function __construct(
        private readonly CustomerServiceInterface $customerService,
        private readonly GetCustomersService $getCustomersService,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        PaginationCriteria $criteria,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->getCustomersService->list(
                $this->getShop(),
                $criteria,
                $this->generateUrl(
                    'api_customers_index',
                    referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                ),
            )
        );
    }

    #[Route('/{uuidOrId}', name: 'show_by_uuid_or_id', methods: ['GET'])]
    public function show(
        string $uuidOrId,
    ): JsonResponse {
        $uuid = $uuidOrId;

        if (false === Uuid::isValid($uuidOrId)) {
            /** @var ?Customer $customer */
            $customer = $this->customerRepository->find($uuidOrId);
            $uuid = $customer->getUuid()->toRfc4122() ?? 'uuid';
        }

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
