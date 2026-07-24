<?php

namespace App\Service\Domain\Ledger\Impl;

use App\Dto\Criteria\Customer\PaginationCriteria;
use App\Dto\Response\Domain\Ledger\CustomerLedgerResponse;
use App\Dto\Response\Domain\PaginationMetaResponse;
use App\Entity\Shop;
use App\Mapper\LedgerEntryMapper;
use App\Repository\LedgerEntryRepository;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use App\Service\Domain\Ledger\CustomerLedgerTimelineBuilder;
use Knp\Component\Pager\PaginatorInterface;

readonly class GetCustomerLedgerService
{
    public function __construct(
        private LedgerEntryRepository $ledgerRepository,
        private LedgerEntryMapper $ledgerMapper,
        private CustomerBalanceService $customerBalanceService,
        private PaginatorInterface $paginator,
        private GetCustomerServiceInterface $getCustomerService,
        private CustomerLedgerTimelineBuilder $timelineBuilder,
    ) {
    }

    public function get(
        Shop $shop,
        string $customerUuid,
        PaginationCriteria $criteria,
    ): CustomerLedgerResponse {
        $customer = $this->getCustomerService->getCustomerByUuidAndShop($customerUuid, $shop);

        $queryBuilder = $this->ledgerRepository->createCustomerHistoryQueryBuilder($customer);
        $pagination = $this->paginator->paginate(
            target: $queryBuilder,
            page: $criteria->page,
            limit: $criteria->limit,
        );

        $entries = $this->timelineBuilder->build($pagination->getItems());

        return new CustomerLedgerResponse(
            statistics: $this->customerBalanceService->getStatistics($customer),
            entries: $entries,
            pagination: new PaginationMetaResponse(
                page: $pagination->getCurrentPageNumber(),
                limit: $pagination->getItemNumberPerPage(),
                total: $pagination->getTotalItemCount(),
                pages: (int) ceil(
                    $pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()
                ),
            ),
        );
    }
}
