<?php

declare(strict_types=1);

namespace App\Service\Domain\Onboarding;

use App\Dto\Command\Domain\OnboardingCommand;
use App\Dto\Command\Security\UserCreateCommand;
use App\Entity\Shop;
use App\Enum\CurrencyEnum;
use App\Enum\ShopStatusEnum;
use App\Enum\ShopTypeEnum;
use App\Service\Security\UserManager;
use App\Validator\OnboardingValidator;
use App\ValueObject\Onboarding;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OnboardingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserManager $userManager,
        private OnboardingValidator $validator,
    ) {
    }

    public function create(
        OnboardingCommand $command,
    ): Onboarding {
        $this->validator->validate($command);

        return $this->entityManager->wrapInTransaction(function () use ($command): Onboarding {
            $shop = $this->createShop($command);

            $this->entityManager->persist($shop);

            $manager = $this->userManager->createManager(
                new UserCreateCommand(
                    firstname: $command->firstname,
                    lastname: $command->lastname,
                    email: $command->email,
                    phone: $command->phone,
                    password: $command->password,
                ),
                $shop
            );

            $this->entityManager->flush();

            return new Onboarding(
                shop: $shop,
                user: $manager,
            );
        });
    }

    private function createShop(
        OnboardingCommand $request,
    ): Shop {
        $shop = new Shop();

        $shop
            ->setName(trim($request->shopName))
            ->setAddress(trim($request->address))
            ->setPostalCode(trim($request->postalCode))
            ->setCity(trim($request->city))
            ->setCountry(trim($request->country))
            ->setPhone(trim($request->shopPhone))
            ->setTimezone(
                $request->timezone ?: 'Europe/Paris'
            )
            ->setCurrency(
                null !== $request->currency
                    ? CurrencyEnum::from($request->currency)
                    : CurrencyEnum::EURO
            )
            ->setStatus(ShopStatusEnum::ACTIVE)
            ->setType(ShopTypeEnum::BUSINESS);

        return $shop;
    }
}
