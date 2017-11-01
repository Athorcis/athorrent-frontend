<?php

namespace Athorrent\Application;

use Athorrent\Cache\CacheCleaner;
use phpFastCache\Helper\Psr16Adapter;
use Silex\Application;

class BaseApplication extends Application
{
    public function __construct()
    {
        parent::__construct(['debug' => DEBUG]);
        
        $this['cache'] = function () {
            return new Psr16Adapter(CACHE_DRIVER, ['ignoreSymfonyNotice' => true]);
        };

        $this['cache.cleaner'] = function (Application $app) {
            return new CacheCleaner($app['cache'], CACHE_DIR);
        };
        
        $this['pdo'] = function () {
            return new \PDO(
                'mysql:host=127.0.0.1;dbname=' . DB_NAME . ';charset=utf8',
                DB_USERNAME,
                DB_PASSWORD,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        };
    }
}
