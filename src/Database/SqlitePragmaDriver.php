<?php

declare(strict_types=1);

namespace Athorrent\Database;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

class SqlitePragmaDriver extends AbstractDriverMiddleware
{
    public function connect(array $params): Connection
    {
        $connection = parent::connect($params);

        $driverName = $params['driver'] ?? '';

        if ($driverName === 'pdo_sqlite' || str_contains($driverName, 'sqlite')) {
            $connection->exec('PRAGMA foreign_keys = ON;');

            $connection->exec('PRAGMA journal_mode=WAL;');
            $connection->exec('PRAGMA synchronous=NORMAL;');
            //$connection->exec('PRAGMA busy_timeout=5000;');
        }

        return $connection;
    }
}
