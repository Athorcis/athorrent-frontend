<?php

namespace Athorrent\Routing;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use function function_exists;
use function in_array;
use function ini_get;
use const FILTER_VALIDATE_BOOL;
use const PHP_SAPI;

class Router implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    protected BaseRouter $baseRouter;

    protected ?array $actionMap = null;

    private ?ConfigCacheFactoryInterface $configCacheFactory = null;

    private static ?array $cache = [];

    public function __construct(
        ContainerInterface $container,
        mixed $resource,
        array $options = [],
        ?RequestContext $context = null,
        ?ContainerInterface $parameters = null,
        ?LoggerInterface $logger = null,
        ?string $defaultLocale = null
    ) {
        $options['generator_class'] = CompiledUrlGenerator::class;
        $this->baseRouter = new BaseRouter($container, $resource, $options, $context, $parameters, $logger, $defaultLocale);
    }

    protected function getActionMapDumperInstance(): ActionMapDumper
    {
        return new ActionMapDumper($this->baseRouter->getRouteCollection());
    }

    public function getActionMap(): array
    {
        if (null !== $this->actionMap) {
            return $this->actionMap;
        }

        $cacheDir = $this->baseRouter->getOption('cache_dir');

        if (null === $cacheDir) {
            $dumper = $this->getActionMapDumperInstance();
            $this->actionMap = $dumper->generateActionMap();
        }
        else {
            $cache = $this->getConfigCacheFactory()->cache($cacheDir.'/action-map.php',
                function (ConfigCacheInterface $cache) {
                    $dumper = $this->getActionMapDumperInstance();

                    $cache->write($dumper->dump(), $this->baseRouter->getRouteCollection()->getResources());
                }
            );

            $this->actionMap = self::readCache($cache->getPath());
        }

        return $this->actionMap;
    }

    public function getGenerator(): UrlGeneratorInterface
    {
        if (isset($this->generator)) {
            return $this->generator;
        }

        $generator = $this->baseRouter->getGenerator();

        if ($generator instanceof CompiledUrlGenerator) {
            $generator->setActionMap($this->getActionMap());
        }

        return $generator;
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        return $this->configCacheFactory ??= new ConfigCacheFactory($this->baseRouter->getOption('debug'));
    }

    private static function readCache(string $path): array
    {
        if ([] === self::$cache && function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL) && (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL))) {
            self::$cache = null;
        }

        if (null === self::$cache) {
            return require $path;
        }

        return self::$cache[$path] ??= require $path;
    }

    public function setContext(RequestContext $context): void
    {
        $this->baseRouter->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->baseRouter->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->baseRouter->getRouteCollection();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo): array
    {
        return $this->baseRouter->match($pathinfo);
    }

    public function matchRequest(Request $request): array
    {
        return $this->baseRouter->matchRequest($request);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return $this->baseRouter->warmUp($cacheDir, $buildDir);
    }
}
