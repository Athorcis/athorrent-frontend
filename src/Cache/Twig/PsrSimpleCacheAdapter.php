<?php

namespace Athorrent\Cache\Twig;

use Asm89\Twig\CacheExtension\CacheProviderInterface;
use Psr\SimpleCache\CacheInterface;

class PsrSimpleCacheAdapter implements CacheProviderInterface
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function fetch($key)
    {
        return $this->cache->get($key, false);
    }

    public function save($key, $value, $lifetime = 0)
    {
        if ($lifetime == 0) {
            $lifetime = null;
        }

        return $this->cache->set($key, $value, $lifetime);
    }
}
