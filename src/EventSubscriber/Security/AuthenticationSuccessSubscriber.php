<?php

declare(strict_types=1);

namespace App\EventSubscriber\Security;

use App\Dto\Response\Infra\ApiSuccessResponse;
use App\Dto\Response\Security\LoginResponse;
use App\Entity\User;
use App\Mapper\UserMapper;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%app.jwt_ttl%')]
        private int $jwtTtl,
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $data = $event->getData();

        $dto = new LoginResponse(
            $data['token'],
            $data['refresh_token'],
            $this->jwtTtl,
            UserMapper::toResponse($user)
        );

        $response = json_decode(
            $this->serializer->serialize(
                new ApiSuccessResponse($dto),
                'json',
                ['skip_null_values' => true]
            ),
            true
        );

        $event->setData($response);
    }
}
