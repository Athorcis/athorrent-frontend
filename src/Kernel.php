<?php

namespace Athorrent;

use Athorrent\Cache\CacheCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,yaml}';

    public function getCacheDir()
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    protected function getConfDir(): string
    {
        return $this->getProjectDir() . '/config';
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/var/log';
    }

    public function boot()
    {
        parent::boot();

        if (!defined('BIN_DIR')) {
            define('BIN_DIR', $this->getProjectDir() . DIRECTORY_SEPARATOR . 'bin');
            define('VAR_DIR', $this->getProjectDir() . DIRECTORY_SEPARATOR . 'var');
            define('FILES_DIR', BIN_DIR . DIRECTORY_SEPARATOR . 'files');
            define('TORRENTS_DIR', VAR_DIR . DIRECTORY_SEPARATOR . 'torrents');
        }
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return iterable|BundleInterface[] An iterable of bundle instances
     */
    public function registerBundles()
    {
        $contents = require $this->getConfDir() . '/bundles.php';

        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    /**
     * @param RouteCollectionBuilder $routes
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getConfDir();

        $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}_' . $this->environment . self::CONFIG_EXTS, '/', 'glob');
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getConfDir();

        $container->addResource(new FileResource($confDir . '/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment.'/**/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
    }

    protected function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CacheCompilerPass());
    }
}
