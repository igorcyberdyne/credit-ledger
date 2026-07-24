<?php

namespace App\Service\Domain\Ledger;

use App\Dto\Criteria\Customer\PaginationCriteria;
use App\Dto\Response\Domain\Customer\CustomerListResponse;
use App\Dto\Response\Domain\PaginationMetaResponse;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Mapper\CustomerMapper;
use App\Repository\CustomerRepository;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use Knp\Component\Pager\PaginatorInterface;

readonly class GetCustomersService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerMapper $customerMapper,
        private CustomerBalanceService $customerBalanceService,
        private PaginatorInterface $paginator,
    ) {
    }

    public function list(
        Shop $shop,
        PaginationCriteria $criteria,
        string $uri,
    ): CustomerListResponse {
        $queryBuilder = $this->customerRepository->createCustomersLedgerHistoryByShopQueryBuilder(
            $shop,
            $criteria->q ?? ''
        );
        $pagination = $this->paginator->paginate(
            target: $queryBuilder,
            page: $criteria->page,
            limit: $criteria->limit,
        );

        $customers = [];
        /** @var Customer $customer */
        foreach ($pagination->getItems() as $customer) {
            $customers[] = $this->customerMapper->toResponse(
                $customer,
                $this->customerBalanceService->getStatistics($customer)
            );
        }

        return new CustomerListResponse(
            customers: $customers,
            pagination: new PaginationMetaResponse(
                page: $pagination->getCurrentPageNumber(),
                limit: $pagination->getItemNumberPerPage(),
                total: $pagination->getTotalItemCount(),
                pages: (int) ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()),
                uri: $uri,
            ),
        );
    }
}
