<?php

namespace Athorrent\Database;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Silex\Provider\DoctrineServiceProvider as BaseDoctrineServiceProvider;

class DoctrineServiceProvider extends BaseDoctrineServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        $config = Setup::createAnnotationMetadataConfiguration([SRC_DIR . '/Database/Entity'], $app['debug']);

        if ($app['debug']) {
            $cache = new \Doctrine\Common\Cache\ArrayCache();
        } else {
            $cache = new \Doctrine\Common\Cache\ApcuCache();
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
