<?php

declare(strict_types=1);

namespace Athorrent\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
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

#[AsDecorator('router')]
class Router implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    protected ?array $actionMap = null;

    private ?ConfigCacheFactoryInterface $configCacheFactory = null;

    private static ?array $cache = [];

    public function __construct(
        private readonly BaseRouter $inner
    ) {
        $this->inner->setOption('generator_class', CompiledUrlGenerator::class);
    }

    protected function getActionMapDumperInstance(): ActionMapDumper
    {
        return new ActionMapDumper($this->inner->getRouteCollection());
    }

    public function getActionMap(): array
    {
        if (null !== $this->actionMap) {
            return $this->actionMap;
        }

        $cacheDir = $this->inner->getOption('cache_dir');

        if (null === $cacheDir) {
            $dumper = $this->getActionMapDumperInstance();
            $this->actionMap = $dumper->generateActionMap();
        }
        else {
            $path = $this->generateActionMapCache($cacheDir);
            $this->actionMap = self::readCache($path);
        }

        return $this->actionMap;
    }

    public function getGenerator(): UrlGeneratorInterface
    {
        if (isset($this->generator)) {
            return $this->generator;
        }

        $generator = $this->inner->getGenerator();

        if ($generator instanceof CompiledUrlGenerator) {
            $generator->setActionMap($this->getActionMap());
        }

        return $generator;
    }

    /**
     * Génère et écrit le cache action-map.php.
     *
     * @param string $cacheDir - Répertoire de cache cible
     */
    private function generateActionMapCache(string $cacheDir): string
    {
        $configCache = $this->getConfigCacheFactory()->cache(
            $cacheDir.'/action-map.php',
            function (ConfigCacheInterface $cache): void {
                $dumper = $this->getActionMapDumperInstance();

                $cache->write(
                    $dumper->dump(),
                    $this->inner->getRouteCollection()->getResources()
                );
            }
        );

        return $configCache->getPath();
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        return $this->configCacheFactory ??= new ConfigCacheFactory($this->inner->getOption('debug'));
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
        $this->inner->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->inner->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->inner->getRouteCollection();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo): array
    {
        return $this->inner->match($pathinfo);
    }

    public function matchRequest(Request $request): array
    {
        return $this->inner->matchRequest($request);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $warmed = $this->inner->warmUp($cacheDir, $buildDir);

        if (null === $this->inner->getOption('cache_dir')) {
            return $warmed;
        }

        $warmed[] = $this->generateActionMapCache($buildDir ?? $cacheDir);

        return $warmed;
    }
}
