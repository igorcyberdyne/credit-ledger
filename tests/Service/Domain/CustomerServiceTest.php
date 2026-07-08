<?php

namespace App\Tests\Service\Domain;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Mapper\CustomerMapper;
use App\Repository\CustomerRepository;
use App\Service\Domain\Customer\CustomerBalanceService;
use App\Service\Domain\Customer\CustomerService;
use App\Validator\CustomerValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CustomerServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private CustomerValidator $validator;

    private CustomerBalanceService $balanceService;
    private CustomerRepository $customerRepository;
    private CustomerMapper $customerMapper;

    private CustomerService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->validator = $this->createMock(CustomerValidator::class);

        $this->balanceService = $this->createMock(CustomerBalanceService::class);

        $this->customerMapper = $this->createMock(CustomerMapper::class);

        $this->service = new CustomerService(
            entityManager: $this->entityManager,
            customerValidator: $this->validator,
            customerBalanceService: $this->balanceService,
            customerMapper: $this->customerMapper
        );
    }

    public function testCreateCustomer(): void
    {
        $command = new CreateCustomerCommand(
            firstname: 'John',
            lastname: 'Doe',
            phone: '0612345678',
            note: 'Client fidèle',
        );

        $customer = $this->createMock(Customer::class);

        $shop = $this->createMock(Shop::class);

        $this->validator
            ->expects(self::once())
            ->method('validateCreate')
            ->with($shop, $command);

        $this->customerMapper
            ->expects(self::once())
            ->method('fromCreateCommand')
            ->with($command)
            ->willReturn($customer);

        $customer
            ->expects(self::once())
            ->method('setShop')
            ->with($shop);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($customer);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->service->create(
            $shop,
            $command,
        );

        self::assertSame($customer, $result);
    }
}
