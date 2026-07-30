<?php

declare(strict_types=1);

namespace App\EventSubscriber\Security;

use App\Dto\Response\Infra\ApiSuccessResponse;
use App\Entity\User;
use App\Service\Security\AuthenticationResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private AuthenticationResponseFactory $factory,
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

        $response = json_decode(
            $this->serializer->serialize(
                new ApiSuccessResponse(
                    $this->factory->create(
                        $user,
                        $data['token'],
                        $data['refresh_token'],
                    )
                ),
                'json',
                ['skip_null_values' => true]
            ),
            true
        );

        $event->setData($response);
    }
}
