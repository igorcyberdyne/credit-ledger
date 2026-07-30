<?php

declare(strict_types=1);

namespace App\Validator;

use App\Dto\Command\Domain\OnboardingCommand;
use App\Enum\CurrencyEnum;
use App\Exception\Domain\OnboardingException;
use App\Repository\ShopRepository;
use App\Repository\UserRepository;

final readonly class OnboardingValidator
{
    public function __construct(
        private UserRepository $userRepository,
        private ShopRepository $shopRepository,
    ) {
    }

    public function validate(OnboardingCommand $dto): void
    {
        $this->validateShopName($dto);

        $this->validateEmail($dto);

        $this->validateCurrency($dto);
    }

    private function validateEmail(OnboardingCommand $dto): void
    {
        if ($this->userRepository->existsByEmail($dto->email)) {
            throw new OnboardingException('Cette adresse email est déjà utilisée.');
        }
    }

    private function validateShopName(OnboardingCommand $dto): void
    {
        if ($this->shopRepository->existsByName($dto->shopName)) {
            throw new OnboardingException('Une boutique avec ce nom existe déjà.');
        }
    }

    private function validateCurrency(OnboardingCommand $dto): void
    {
        if (null === $dto->currency) {
            return;
        }

        if (null !== CurrencyEnum::tryFrom($dto->currency)) {
            return;
        }

        throw new OnboardingException('Devise inconnue.');
    }
}
