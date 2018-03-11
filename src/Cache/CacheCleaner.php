<?php

namespace Athorrent\Cache;

use Athorrent\Filesystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

class CacheCleaner
{
    private $cache;

    private $cacheDir;

    private $filesystem;

    public function __construct(CacheInterface $cache, $cacheDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
        $this->filesystem = new Filesystem('/');
    }

    public function clearApplicationCache()
    {
        return $this->cache->clear();
    }

    protected function clearCacheDir($subdir)
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $subdir;

        if (is_dir($path)) {
            $this->filesystem->remove($path);
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
