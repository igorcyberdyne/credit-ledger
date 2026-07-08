<?php

namespace App\Controller\RestApi;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Service\Domain\Customer\CustomerServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customers', name: 'customer_')]
class CustomerController extends ApiController
{
    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(CustomerServiceInterface $service, CreateCustomerCommand $command): JsonResponse
    {
        return $this->apiCreated($service->create($this->getAuthenticatedUser()->getShop(), $command));
    }
}
