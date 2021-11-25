<?php

namespace Athorrent\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Routing\Generator\Dumper\CompiledUrlGeneratorDumper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function function_exists;
use function in_array;
use const PHP_SAPI;

class Router extends BaseRouter
{
    protected $actionMap;

    /**
     * @var ConfigCacheFactoryInterface|null
     */
    private $configCacheFactory;

    private static $cache = [];

    protected function getActionMapDumperInstance()
    {
        return new ActionMapDumper($this->getRouteCollection());
    }

    public function getActionMap(): array
    {
        if (null !== $this->actionMap) {
            return $this->actionMap;
        }

        if (null === $this->options['cache_dir']) {
            $dumper = $this->getActionMapDumperInstance();
            $this->actionMap = $dumper->generateActionMap();
        }
        else {
            $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/action-map.php',
                function (ConfigCacheInterface $cache) {
                    $dumper = $this->getActionMapDumperInstance();

                    $cache->write($dumper->dump(), $this->getRouteCollection()->getResources());
                }
            );

            $this->actionMap = self::readCache($cache->getPath());
        }

        return $this->actionMap;
    }

    public function getGenerator(): UrlGeneratorInterface
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        if (null === $this->options['cache_dir']) {
            $routes = $this->getRouteCollection();
            $routes = (new CompiledUrlGeneratorDumper($routes))->getCompiledRoutes();

            $this->generator = new CompiledUrlGenerator($this->getActionMap(), $routes, $this->context, $this->logger, $this->defaultLocale);
        } else {
            $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/url_generating_routes.php',
                function (ConfigCacheInterface $cache) {
                    $dumper = $this->getGeneratorDumperInstance();

                    $cache->write($dumper->dump(), $this->getRouteCollection()->getResources());
                }
            );

            $this->generator = new CompiledUrlGenerator($this->getActionMap(), self::readCache($cache->getPath()), $this->context, $this->logger, $this->defaultLocale);
        }

        $this->generator->setStrictRequirements($this->options['strict_requirements']);

        return $this->generator;
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        if (null === $this->configCacheFactory) {
            $this->configCacheFactory = new ConfigCacheFactory($this->options['debug']);
        }

        return $this->configCacheFactory;
    }

    private static function readCache(string $path): array
    {
        if ([] === self::$cache && function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) && (!in_array(
                    PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN))) {
            self::$cache = null;
        }

        if (null === self::$cache) {
            return require $path;
        }

        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        return self::$cache[$path] = require $path;
    }
}
