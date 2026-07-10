<?php

namespace App\Controller\RestApi\Domain;

use App\Controller\RestApi\ApiController;
use App\Dto\Command\Ledger\CorrectLedgerEntryCommand;
use App\Dto\Command\Ledger\CreateDebtCommand;
use App\Dto\Command\Ledger\CreatePaymentCommand;
use App\Dto\Command\Ledger\ReverseLedgerEntryCommand;
use App\Dto\Criteria\Customer\PaginationCriteria;
use App\Service\Domain\Ledger\Impl\CorrectLedgerEntryService;
use App\Service\Domain\Ledger\Impl\CreateDebtService;
use App\Service\Domain\Ledger\Impl\CreatePaymentService;
use App\Service\Domain\Ledger\Impl\GetCustomerLedgerService;
use App\Service\Domain\Ledger\Impl\ReverseLedgerEntryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ledgers', name: 'ledgers_')]
final class LedgerController extends ApiController
{
    public function __construct(
        private readonly GetCustomerLedgerService $getCustomerLedgerService,
        private readonly CreateDebtService $createDebtService,
        private readonly CreatePaymentService $createPaymentService,
        private readonly ReverseLedgerEntryService $reverseLedgerEntryService,
        private readonly CorrectLedgerEntryService $correctLedgerEntryService,
    ) {
    }

    #[Route('/customers/{customerUuid}/ledger', name: 'customer_ledger', methods: ['GET'])]
    public function customerLedger(
        string $customerUuid,
        #[MapQueryString]
        PaginationCriteria $pagination,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->getCustomerLedgerService->get(
                $this->getShop(),
                $customerUuid,
                $pagination,
            )
        );
    }

    #[Route('/customers/{customerUuid}/debts', name: 'create_debt', methods: ['POST'])]
    public function createDebt(
        string $customerUuid,
        #[MapRequestPayload]
        CreateDebtCommand $command,
    ): JsonResponse {
        return $this->apiCreated(
            $this->createDebtService->create(
                $this->getShop(),
                $customerUuid,
                $command,
            )
        );
    }

    #[Route('/customers/{customerUuid}/payments', name: 'create_payment', methods: ['POST'])]
    public function createPayment(
        string $customerUuid,
        #[MapRequestPayload]
        CreatePaymentCommand $command,
    ): JsonResponse {
        return $this->apiCreated(
            $this->createPaymentService->create(
                $this->getShop(),
                $customerUuid,
                $command,
            ),
        );
    }

    #[Route('/ledger/{uuid}/reverse', name: 'reverse', methods: ['POST'])]
    public function reverse(
        string $uuid,
        #[MapRequestPayload]
        ReverseLedgerEntryCommand $command,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->reverseLedgerEntryService->reverse(
                $this->getShop(),
                $uuid,
                $command
            )
        );
    }

    #[Route('/ledger/{uuid}/correct', name: 'correct', methods: ['POST'])]
    public function correct(
        string $uuid,
        #[MapRequestPayload]
        CorrectLedgerEntryCommand $command,
    ): JsonResponse {
        return $this->apiSuccess(
            $this->correctLedgerEntryService->correct(
                $this->getShop(),
                $uuid,
                $command,
            )
        );
    }
}
