<?php

namespace Athorrent\Utils\Cache;

abstract class Cache {
    public abstract function exists($key);

    public abstract function fetch($key);

    public abstract function store($key, $value, $lifetime = 0);

    private static $instances;

    public static function getInstance($cacheType = CACHE_TYPE) {
        if (!isset(self::$instances[$cacheType])) {
            self::$instances[$cacheType] = new $cacheType();
        }

        return self::$instances[$cacheType];
    }
}

?>
