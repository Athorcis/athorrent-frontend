<?php

namespace Athorrent\Cache\Twig;

use Phpfastcache\Bundle\DataCollector\CacheCollector;
use Phpfastcache\Bundle\Twig\CacheExtension\CacheProviderInterface;
use Phpfastcache\Bundle\Twig\CacheExtension\CacheStrategyInterface;

class GenerationalCacheStrategy implements CacheStrategyInterface
{
    /**
     * @var string
     */
    private $twigCachePrefix = '_TwigCacheLCS_';

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var CacheProviderInterface
     */
    private $cache;

    /**
     * @var CacheCollector
     */
    private $cacheCollector;

    private $keyGenerator;

    /**
     * LifetimeCacheStrategy constructor.
     * @param CacheProviderInterface $cache
     * @param CacheCollector $cacheCollector
     * @param array $config
     * @param KeyGeneratorInterface $keyGenerator
     */
    public function __construct(CacheProviderInterface $cache, CacheCollector $cacheCollector = null, $config, KeyGeneratorInterface $keyGenerator)
    {
        $this->cache = $cache;
        $this->cacheCollector = $cacheCollector;
        $this->config = (array) $config;
        $this->keyGenerator = $keyGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchBlock($key, \Twig_Source $sourceContex)
    {
        $generationTimeMc = microtime(true);
        $cacheData = $this->cache->fetch($key);
        $generationTime = microtime(true) - $generationTimeMc;
        $unprefixedKey = substr($key, strlen($this->twigCachePrefix));

        if ($this->cacheCollector instanceof CacheCollector) {
            $this->cacheCollector->setTwigCacheBlock($unprefixedKey, [
                'cacheHit' => $cacheData !== null,
                'cacheTtl' => 0,
                'cacheSize' => mb_strlen((string) $cacheData),
                'cacheGenTime' => $generationTime
            ]);
        }

        if (!empty($cacheData) && $this->config['twig_block_debug']) {
            return "<!-- BEGIN CACHE BLOCK OUTPUT '{$unprefixedKey}' -->\n{$cacheData}\n<!-- // END CACHE BLOCK OUTPUT '{$unprefixedKey}' -->";
        }

        return $cacheData;
    }

    /**
     * {@inheritDoc}
     */
    public function generateKey($annotation, $value)
    {
        return $annotation . '|' . $this->keyGenerator->generateKey($value);
    }

    /**
     * {@inheritDoc}
     */
    public function saveBlock($key, $block, $generationTime, \Twig_Source $sourceContext)
    {
        $unprefixedKey = substr($key, strlen($this->twigCachePrefix));

        if ($this->cacheCollector instanceof CacheCollector) {
            $this->cacheCollector->setTwigCacheBlock($unprefixedKey, [
                'cacheHit' => false,
                'cacheTtl' => 0,
                'cacheSize' => mb_strlen((string) $block),
                'cacheGenTime' => $generationTime
            ]);
        }

        return $this->cache->save($key, $block);
    }
}
