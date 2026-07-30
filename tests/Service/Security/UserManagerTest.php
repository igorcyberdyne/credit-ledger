<?php

namespace App\Tests\Service\Security;

use App\Dto\Command\Security\UserCreateCommand;
use App\Entity\Shop;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Service\Security\UserManager;
use App\Tests\Tools\BasicTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManagerTest extends BasicTestCase
{
    private UserManager $userManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $passwordHasher
            ->expects(self::once())
            ->method('hashPassword');
        $entityManager
            ->expects(self::once())
            ->method('persist');

        $this->userManager = new UserManager(
            $entityManager,
            $passwordHasher,
        );
    }

    public static function userAccordingRoleDataProvider(): array
    {
        return [
            [
                'userRoleEnum' => UserRoleEnum::MANAGER,
                'expectedRoles' => [UserRoleEnum::MANAGER->value],
            ],
            [
                'userRoleEnum' => UserRoleEnum::EMPLOYEE,
                'expectedRoles' => [UserRoleEnum::EMPLOYEE->value],
            ],
        ];
    }

    #[DataProvider(methodName: 'userAccordingRoleDataProvider')]
    public function testPersistUserAccordingRole(UserRoleEnum $userRoleEnum, array $expectedRoles): void
    {
        $command = new UserCreateCommand(
            firstname: $this->getGenerator()->firstName(),
            lastname: $this->getGenerator()->lastName(),
            email: $this->getGenerator()->email(),
            phone: $this->getGenerator()->phoneNumber(),
            password: $this->getGenerator()->password(),
        );

        $shop = new Shop();
        $userPersisted = match ($userRoleEnum) {
            UserRoleEnum::MANAGER => $this->userManager->createManager(
                $command,
                $shop
            ),
            UserRoleEnum::EMPLOYEE => $this->userManager->createEmployee(
                $command,
                $shop
            ),
        };

        $this->assertEquals($expectedRoles, $userPersisted->getRoles());
        $this->assertEquals(UserStatusEnum::DISABLED, $userPersisted->getStatus());
        $this->assertEquals($shop, $userPersisted->getShop());
    }
}
