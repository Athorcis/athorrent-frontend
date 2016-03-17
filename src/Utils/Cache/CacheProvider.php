<?php

namespace Athorrent\Utils\Cache;

use Asm89\Twig\CacheExtension\CacheProviderInterface;

class CacheProvider implements CacheProviderInterface
{
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function fetch($key)
    {
        return $this->cache->fetch($key);
    }

    public function save($key, $value, $lifetime = 0)
    {
        return $this->cache->store($key, $value, $lifetime);
    }
}
