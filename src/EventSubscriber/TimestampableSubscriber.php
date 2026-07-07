<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Contracts\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class TimestampableSubscriber
{
    public function prePersist(
        PrePersistEventArgs $event,
    ): void {
        $entity = $event->getObject();
        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = new \DateTimeImmutable();
        if (null === $entity->getCreatedAt()) {
            $entity->setCreatedAt($now);
        }

        $entity->setUpdatedAt($now);
    }

    public function preUpdate(
        PreUpdateEventArgs $event,
    ): void {
        $entity = $event->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $entity->setUpdatedAt(new \DateTimeImmutable());
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metadata = $entityManager->getClassMetadata($entity::class);
        $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
    }
}
