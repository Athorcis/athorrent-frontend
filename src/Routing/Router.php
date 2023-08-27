<?php

namespace Athorrent\Routing;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class Router extends BaseRouter
{
    protected ?array $actionMap = null;

    private ?ConfigCacheFactoryInterface $configCacheFactory = null;

    private static ?array $cache = [];

    public function __construct(
        ContainerInterface $container,
        mixed $resource,
        array $options = [],
        RequestContext $context = null,
        ContainerInterface $parameters = null,
        LoggerInterface $logger = null,
        string $defaultLocale = null
    ) {

        $options['generator_class'] = CompiledUrlGenerator::class;
        parent::__construct($container, $resource, $options, $context, $parameters, $logger, $defaultLocale);
    }

    protected function getActionMapDumperInstance(): ActionMapDumper
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
        if ($this->generator instanceof UrlGeneratorInterface) {
            return $this->generator;
        }

        $generator = parent::getGenerator();
        $generator->setActionMap($this->getActionMap());

        return $generator;
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        return $this->configCacheFactory ??= new ConfigCacheFactory($this->options['debug']);
    }

    private static function readCache(string $path): array
    {
        if ([] === self::$cache && \function_exists('opcache_invalidate') && filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOL) && (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(\ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOL))) {
            self::$cache = null;
        }

        if (null === self::$cache) {
            return require $path;
        }

        return self::$cache[$path] ??= require $path;
    }

    /**
     * This method needs to be overridden or else it causes an error on Symfony 6.2+
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return [];
    }
}
