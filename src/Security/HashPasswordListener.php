<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
readonly class HashPasswordListener
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    private function encodePassword(User $entity): void
    {
        if (!$entity->getPlainPassword()) {
            return;
        }

        $encoded = $this->passwordHasher->hashPassword(
            $entity,
            $entity->getPlainPassword()
        );

        $entity->eraseCredentials();
        $entity->setPassword($encoded);
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $this->encodePassword($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $this->encodePassword($entity);

        // necessary to force the update to see the change
        $em = $args->getObjectManager();
        $meta = $em->getClassMetadata($entity::class);
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $entity);
    }
}
