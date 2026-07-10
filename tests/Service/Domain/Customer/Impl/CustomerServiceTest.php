<?php

namespace App\Tests\Service\Domain\Customer\Impl;

use App\Dto\Command\Customer\CreateCustomerCommand;
use App\Entity\Customer;
use App\Entity\Shop;
use App\Mapper\CustomerMapper;
use App\Service\Domain\Customer\Contracts\GetCustomerServiceInterface;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use App\Service\Domain\Customer\Impl\CustomerService;
use App\Validator\CustomerValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CustomerServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private CustomerValidator $validator;

    private CustomerMapper $customerMapper;

    private CustomerService $service;

    private CustomerBalanceService $customerBalanceService;

    private GetCustomerServiceInterface $getCustomerService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->validator = $this->createMock(CustomerValidator::class);

        $this->customerMapper = $this->createMock(CustomerMapper::class);

        $this->customerBalanceService = $this->createMock(CustomerBalanceService::class);

        $this->getCustomerService = $this->createMock(GetCustomerServiceInterface::class);

        $this->service = new CustomerService(
            entityManager: $this->entityManager,
            customerValidator: $this->validator,
            customerMapper: $this->customerMapper,
            customerBalanceService: $this->customerBalanceService,
            getCustomerService: $this->getCustomerService
        );
    }

    public static function commandDataProvider(): array
    {
        return [
            'New Customer' => [
                'command' => new CreateCustomerCommand(
                    firstname: 'John',
                    lastname: 'Doe',
                    phone: null,
                    note: 'Client fidèle',
                ),
            ],
            'Old Customer to reactivate' => [
                'command' => new CreateCustomerCommand(
                    firstname: 'John',
                    lastname: 'Doe',
                    phone: '0650102834',
                    note: 'Client fidèle',
                ),
            ],
        ];
    }

    #[DataProvider(methodName: 'commandDataProvider')]
    public function testCreateCustomer(CreateCustomerCommand $command): void
    {
        $shop = $this->createMock(Shop::class);

        $uuidMock = $this->createMock(Uuid::class);
        $uuidMock
            ->expects(self::once())
            ->method('toRfc4122')
            ->willReturn(Uuid::v7()->toRfc4122())
        ;

        $customer = $this->createMock(Customer::class);
        $customer
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($uuidMock);
        $customer
            ->expects(self::once())
            ->method('getFirstname')
            ->willReturn($command->firstname);
        $customer
            ->expects(self::once())
            ->method('getLastname')
            ->willReturn($command->lastname);
        $customer
            ->expects(self::once())
            ->method('getPhone')
            ->willReturn($command->phone);

        if (!empty($command->phone)) {
            $filters = $this->createMock(FilterCollection::class);
            $filters->expects(self::once())->method('disable')->with('softdeleteable');
            $filters->expects(self::once())->method('enable')->with('softdeleteable');

            $this->entityManager
                ->expects(self::once())
                ->method('getFilters')
                ->willReturn($filters);

            $this->getCustomerService
                ->expects(self::once())
                ->method('getCustomerByPhoneAndShop')
                ->with($command->phone, $shop)
                ->willReturn($customer);

            $updateCommand = new CustomerMapper()->fromCreateCustomerCommandToUpdateCustomerCommand($command);
            $this->customerMapper
                ->expects(self::once())
                ->method('fromCreateCustomerCommandToUpdateCustomerCommand')
                ->with($command)
                ->willReturn($updateCommand);

            $this->customerMapper
                ->expects(self::once())
                ->method('updateEntity')
                ->with($customer, $updateCommand)
                ->willReturn($customer);

            $customer
                ->expects(self::once())
                ->method('setDeletedAt')
                ->with(null)
                ->willReturnSelf();

            $customer
                ->expects(self::once())
                ->method('setDeletedBy')
                ->with(null)
                ->willReturnSelf();
        } else {
            $this->validator
                ->expects(self::once())
                ->method('validateCreate')
                ->with($shop, $command);

            $this->customerMapper
                ->expects(self::once())
                ->method('fromCreateCustomerCommand')
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
        }

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $expectedCustomer = new CustomerMapper()->toResponse($customer);
        $this->customerMapper
            ->expects(self::once())
            ->method('toResponse')
            ->with($customer)
            ->willReturn($expectedCustomer);

        self::assertSame(
            $expectedCustomer,
            $this->service->create($shop, $command)
        );
    }
}
