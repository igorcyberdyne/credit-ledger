<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Contracts\BlameableInterface;
use App\Entity\User;
use App\Service\Security\Provider\CurrentUserProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final readonly class BlameableSubscriber
{
    public function __construct(
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    public function prePersist(
        PrePersistEventArgs $event,
    ): void {
        $entity = $event->getObject();

        if (!$entity instanceof BlameableInterface) {
            return;
        }

        $user = $this->currentUserProvider->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (null === $entity->getCreatedBy()) {
            $entity->setCreatedBy($user);
        }

        $entity->setUpdatedBy($user);
    }

    public function preUpdate(
        PreUpdateEventArgs $event,
    ): void {
        $entity = $event->getObject();
        if (!$entity instanceof BlameableInterface) {
            return;
        }

        $user = $this->currentUserProvider->getUser();
        if (!$user instanceof User) {
            return;
        }

        $entity->setUpdatedBy($user);
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metadata = $entityManager->getClassMetadata($entity::class);
        $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
    }
}
