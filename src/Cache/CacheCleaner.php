<?php

namespace Athorrent\Cache;

use Psr\SimpleCache\CacheInterface;

class CacheCleaner
{
    private $cache;

    private $cacheDir;

    public function(CacheInterface $cache, $cacheDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
    }

    public function cleanApplicationCache()
    {
        $this->cache->clear();
    }

    protected function cleanCacheDir($subdir)
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $subdir;

        if (is_dir($path)) {
            return FileUtils::rrmdir($path);
        }

        return true;
    }

    public function clearTwigCache()
    {
        return $this->cleanCacheDir('twig');
    }

    public function clearTranslationsCache()
    {
        return $this->cleanCacheDir('translator');
    }
}
