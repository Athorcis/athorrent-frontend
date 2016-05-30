<?php

namespace Athorrent\Application;

use PDO;
use Silex\Application;

class BaseApplication extends Application
{
    public function __construct()
    {
        parent::__construct(['debug' => DEBUG]);
        
        $this['cache'] = $this->share(function () {
            return \Athorrent\Utils\Cache\Cache::getInstance();
        });
        
        $this['pdo'] = $this->share(function () {
            return new PDO(
                'mysql:host=127.0.0.1;dbname=' . DB_NAME . ';charset=utf8',
                DB_USERNAME,
                DB_PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        });
    }
}
