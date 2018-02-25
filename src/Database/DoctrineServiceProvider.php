<?php

namespace Athorrent\Database;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Silex\Provider\DoctrineServiceProvider as BaseDoctrineServiceProvider;

class DoctrineServiceProvider extends BaseDoctrineServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        $config = Setup::createAnnotationMetadataConfiguration([SRC_DIR . '/Database/Entity'], DEBUG);

        if (function_exists('apcu_exists') && !DEBUG) {
            $cache = new ApcuCache();
        } else {
            $cache = new ArrayCache();
        }

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $app['orm.em'] = function () use ($app, $config) {
            $connection = $app['db'];
            $platform = $connection->getDatabasePlatform();

            $platform->registerDoctrineTypeMapping('enum', 'string');

            return EntityManager::create($connection, $config);
        };
    }
}
