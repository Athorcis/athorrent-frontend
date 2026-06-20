<?php

declare(strict_types=1);

namespace Athorrent\Database;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

#[AsMiddleware(connections: ['default'])]
class SqlitePragmaMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new SqlitePragmaDriver($driver);
    }
}
