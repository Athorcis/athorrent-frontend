<?php

namespace Athorrent\Utils\Cache;

abstract class Cache
{
    abstract public function exists($key);

    abstract public function fetch($key);

    abstract public function store($key, $value, $lifetime = 0);

    private static $instances;

    public static function getInstance($cacheType = CACHE_TYPE)
    {
        if (!isset(self::$instances[$cacheType])) {
            self::$instances[$cacheType] = new $cacheType();
        }

        return self::$instances[$cacheType];
    }
}
