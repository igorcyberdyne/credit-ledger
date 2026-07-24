<?php

declare(strict_types=1);

namespace App\EventSubscriber\Security;

use App\Dto\Response\Infra\ApiErrorResponse;
use App\Dto\Response\Infra\ApiResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class JwtSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_EXPIRED => 'onJwtExpired',
            Events::JWT_INVALID => 'onJwtInvalid',
            Events::JWT_NOT_FOUND => 'onJwtNotFound',
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    private function createResponse(
        string $code,
        string $message,
    ): JsonResponse {
        $data = new ApiResponse(apiErrorResponse: new ApiErrorResponse($code, $message));

        return new JsonResponse($this->serializer->serialize($data, 'json', ['skip_null_values' => true]), Response::HTTP_UNAUTHORIZED, json: true);
    }

    /**
     * @throws ExceptionInterface
     */
    public function onJwtExpired(
        JWTExpiredEvent $event,
    ): void {
        $event->setResponse(
            $this->createResponse(
                code: 'TOKEN_EXPIRED',
                message: 'Votre session a expiré. Veuillez vous reconnecter.'
            )
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function onJwtInvalid(
        JWTInvalidEvent $event,
    ): void {
        $event->setResponse(
            $this->createResponse(
                code: 'INVALID_TOKEN',
                message: 'Le token fourni est invalide.'
            )
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function onJwtNotFound(
        JWTNotFoundEvent $event,
    ): void {
        $event->setResponse(
            $this->createResponse(
                code: 'TOKEN_MISSING',
                message: 'Aucun token d’authentification n’a été fourni.'
            )
        );
    }
}
