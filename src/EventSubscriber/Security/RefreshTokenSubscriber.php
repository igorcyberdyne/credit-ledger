<?php

declare(strict_types=1);

namespace App\EventSubscriber\Security;

use App\Dto\Response\Infra\ApiErrorResponse;
use App\Dto\Response\Infra\ApiResponse;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshAuthenticationFailureEvent;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshTokenNotFoundEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class RefreshTokenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'gesdinet.refresh_token_failure' => 'onRefreshTokenFailure',
            'gesdinet.refresh_token_not_found' => 'onRefreshTokenNotFound',
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    private function createResponse(
        string $apiCode,
        string $apiMessage,
        string $methodCalled,
        AuthenticationException $exception,
    ): JsonResponse {
        $this->logger->error(sprintf(
            'apiCode: %s, apiMessage: %s',
            $apiCode,
            $apiMessage,
        ));
        $this->logger->error(sprintf(
            'Func: %s, Error: %s',
            $methodCalled,
            $exception->getMessageKey(),
        ), $exception->getTrace());

        $data = new ApiResponse(apiErrorResponse: new ApiErrorResponse($apiCode, $apiMessage));

        return new JsonResponse($this->serializer->serialize($data, 'json', ['skip_null_values' => true]), Response::HTTP_UNAUTHORIZED, json: true);
    }

    /**
     * @throws ExceptionInterface
     */
    public function onRefreshTokenFailure(RefreshAuthenticationFailureEvent $event): void
    {
        $event->setResponse(
            $this->createResponse(
                apiCode: 'INVALID_REFRESH_TOKEN',
                apiMessage: 'Invalid refresh token',
                methodCalled: __METHOD__,
                exception: $event->getException()
            )
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function onRefreshTokenNotFound(RefreshTokenNotFoundEvent $event): void
    {
        $event->setResponse(
            $this->createResponse(
                apiCode: 'REFRESH_TOKEN_NOT_FOUND',
                apiMessage: 'Refresh token not found',
                methodCalled: __METHOD__,
                exception: $event->getException()
            )
        );
    }
}
