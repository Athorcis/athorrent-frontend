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
    private $keyPrefix = '_Twig_GCS_';

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

    /**
     * @var KeyGeneratorInterface
     */
    private $keyGenerator;

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
    public function fetchBlock($key, \Twig_Source $sourceContext)
    {
        $generationTimeMc = microtime(true);
        $cacheData = $this->cache->fetch($key);
        $generationTime = microtime(true) - $generationTimeMc;
        $unprefixedKey = substr($key, strlen($this->keyPrefix));

        if ($this->cacheCollector instanceof CacheCollector) {
            $this->cacheCollector->setTwigCacheBlock($unprefixedKey, [
                'cacheHit' => $cacheData !== null,
                'cacheTtl' => 0,
                'cacheSize' => mb_strlen((string) $cacheData),
                'cacheGenTime' => $generationTime,
                'cacheFileName' => $sourceContext->getName(),
                'cacheFilePath' => $sourceContext->getPath(),
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
        return $this->keyPrefix . $annotation . '|' . $this->keyGenerator->generateKey($value);
    }

    /**
     * {@inheritDoc}
     */
    public function saveBlock($key, $block, $generationTime, \Twig_Source $sourceContext)
    {
        $unprefixedKey = substr($key, strlen($this->keyPrefix));

        if ($this->cacheCollector instanceof CacheCollector) {
            $this->cacheCollector->setTwigCacheBlock($unprefixedKey, [
                'cacheHit' => false,
                'cacheTtl' => 0,
                'cacheSize' => mb_strlen((string) $block),
                'cacheGenTime' => $generationTime,
                'cacheFileName' => $sourceContext->getName(),
                'cacheFilePath' => $sourceContext->getPath(),
            ]);
        }

        return $this->cache->save($key, $block);
    }
}
