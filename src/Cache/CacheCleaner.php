<?php

namespace Athorrent\Cache;

use Athorrent\Filesystem\Filesystem;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Filesystem\Path;

class CacheCleaner
{
    private CacheInterface $cache;

    private string $cacheDir;

    private Filesystem $filesystem;

    public function __construct(CacheInterface $cache, string $cacheDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
        $this->filesystem = new Filesystem('/');
    }

    public function clearApplicationCache(): bool
    {
        return $this->cache->clear();
    }

    protected function clearCacheDir($subdir): bool
    {
        $path = Path::join($this->cacheDir, $subdir);

        if (is_dir($path)) {
            $this->filesystem->remove($path);
        }

        return true;
    }

    public function clearTwigCache(): bool
    {
        return $this->clearCacheDir('twig');
    }

    public function clearTranslationsCache(): bool
    {
        return $this->clearCacheDir('translator');
    }
}
