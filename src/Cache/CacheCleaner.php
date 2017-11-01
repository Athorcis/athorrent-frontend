<?php

namespace Athorrent\Cache;

use Athorrent\Filesystem\FileUtils;
use Psr\SimpleCache\CacheInterface;

class CacheCleaner
{
    private $cache;

    private $cacheDir;

    public function __construct(CacheInterface $cache, $cacheDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
    }

    public function clearApplicationCache()
    {
        return $this->cache->clear();
    }

    protected function clearCacheDir($subdir)
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $subdir;

        if (is_dir($path)) {
            return FileUtils::rrmdir($path);
        }

        return true;
    }

    public function clearTwigCache()
    {
        return $this->clearCacheDir('twig');
    }

    public function clearTranslationsCache()
    {
        return $this->clearCacheDir('translator');
    }
}
