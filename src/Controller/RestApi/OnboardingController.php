<?php

namespace App\Controller\RestApi;

use App\Dto\Command\Domain\OnboardingCommand;
use App\Service\Domain\Onboarding\OnboardingService;
use App\Service\Security\AuthenticationResponseFactory;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class OnboardingController extends ApiController
{
    #[Route('/onboarding', name: 'onboarding', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload]
        OnboardingCommand $command,
        OnboardingService $onboardingService,
        AuthenticationResponseFactory $factory,
        JWTTokenManagerInterface $tokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
    ): JsonResponse {
        $onboarding = $onboardingService->create($command);

        return $this->apiCreated(
            $factory->create(
                user: $onboarding->user,
                token: $tokenManager->create($onboarding->user),
                refreshToken: $refreshTokenGenerator->createForUserWithTtl($onboarding->user, $factory->getJwtTtl())
            )
        );
    }
}
