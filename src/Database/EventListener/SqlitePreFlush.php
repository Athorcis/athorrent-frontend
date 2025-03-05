<?php

namespace Athorrent\Database\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preFlush, priority: 500, connection: 'default')]
class SqlitePreFlush
{
    /**
     * @throws Exception
     */
    public function preFlush(PreFlushEventArgs $args): void
    {
        $connection = $args->getObjectManager()->getConnection();

        if ($connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = ON;');
        }
    }
}
