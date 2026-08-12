<?php

namespace App\Tests\Service\Domain\Onboarding;

use App\Dto\Command\Domain\OnboardingCommand;
use App\Enum\CurrencyEnum;
use App\Exception\Domain\OnboardingException;
use App\Service\Domain\Onboarding\OnboardingService;
use App\Tests\Tools\BasicTestCase;

class OnboardingServiceTest extends BasicTestCase
{
    private OnboardingService $onboardingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->onboardingService = $this->getService(OnboardingService::class);
    }

    private function givenCreateCommand(
        ?string $shopName = null,
        ?string $managerEmail = null,
        ?string $currency = null,
    ): OnboardingCommand {
        return new OnboardingCommand(
            shopName: $shopName ?? $this->getGenerator()->name(),
            address: $this->getGenerator()->address(),
            postalCode: $this->getGenerator()->postcode(),
            city: $this->getGenerator()->city(),
            country: $this->getGenerator()->countryCode(),
            shopPhone: $this->getGenerator()->phoneNumber(),
            currency: $currency ?? CurrencyEnum::EURO->name,
            timezone: $this->getGenerator()->timezone(),

            firstname: $this->getGenerator()->firstName(),
            lastname: $this->getGenerator()->lastName(),
            email: $managerEmail ?? $this->getGenerator()->email(),
            phone: $this->getGenerator()->phoneNumber(),
            password: $this->getGenerator()->password(),
        );
    }

    /**
     * @throws \Throwable
     */
    public function testOnboarding(): void
    {
        $this->wrapInRollback(function () {
            $command = $this->givenCreateCommand('Z Magazine', 'z-manager@zmagazine.fr');

            $onboarding = $this->onboardingService->create($command);

            $this->assertEquals($command->shopName, $onboarding->shop->getName());
            $this->assertEquals($command->email, $onboarding->user->getEmail());
            $this->assertTrue($onboarding->user->isManager());
        });
    }

    /**
     * @throws \Throwable
     */
    public function testOnboardingWithSameShopName(): void
    {
        $this->wrapInRollback(function () {
            $shopName = $this->getGenerator()->name();
            $email = $this->getGenerator()->email();

            $command = $this->givenCreateCommand($shopName, $email);
            $this->onboardingService->create($command);

            $this->expectException(OnboardingException::class);
            $this->expectExceptionMessage('Une boutique avec ce nom existe déjà.');

            $this->onboardingService->create(
                $this->givenCreateCommand($shopName, $email)
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testOnboardingWithSameManagerEmail(): void
    {
        $this->wrapInRollback(function () {
            $email = $this->getGenerator()->email();
            $command = $this->givenCreateCommand('Shop One', $email);
            $this->onboardingService->create($command);

            $this->expectException(OnboardingException::class);
            $this->expectExceptionMessage('Cette adresse email est déjà utilisée.');

            $this->onboardingService->create(
                $this->givenCreateCommand('Shop Two', $email)
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function testOnboardingWithUnknowingCurrency(): void
    {
        $this->wrapInRollback(function () {
            $command = $this->givenCreateCommand(
                $this->getGenerator()->name(),
                $this->getGenerator()->email(),
                'FAKE'
            );

            $this->expectException(OnboardingException::class);
            $this->expectExceptionMessage('Devise inconnue.');

            $this->onboardingService->create($command);
        });
    }
}
