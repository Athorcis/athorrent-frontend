<?php

namespace Athorrent\Service;

use Asm89\Twig\CacheExtension\CacheStrategy\GenerationalCacheStrategy;
use Asm89\Twig\CacheExtension\Extension as CacheExtension;
use Athorrent\Utils\Cache\CacheProvider;
use Athorrent\Utils\Cache\KeyGenerator;
use Silex\Application;
use Silex\ServiceProviderInterface;
use SPE\FilesizeExtensionBundle\Twig\FilesizeExtension;
use Twig_Environment;
use Twig_SimpleFunction;

class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->register(new \Silex\Provider\TwigServiceProvider(), [
            'twig.path' => APP . '/views',
            'twig.options' => ['cache' => CACHE . '/twig']
        ]);

        $app['twig'] = $app->share($app->extend('twig', function (Twig_Environment $twig) use ($app) {
            return $this->extendTwig($twig, $app);
        }));
    }

    public function extendTwig(Twig_Environment $twig, Application $app)
    {
        $this->initializeCache($twig, $app);

        $twig->addExtension(new FilesizeExtension());

        $twig->addFunction(new Twig_SimpleFunction('torrentStateToClass', [$this, 'torrentStateToClass']));

        $twig->addFunction(new Twig_SimpleFunction('css', [$this, 'includeCss']));

        $twig->addFunction(new Twig_SimpleFunction('js', [$this, 'includeJs']));

        $twig->addFunction(new Twig_SimpleFunction('path', function ($action, $parameters = [], $prefixAction = null) use ($app) {
            return $app['alias_resolver']->generatePath($action, $parameters, $prefixAction);
        }));

        $twig->addFunction(new Twig_SimpleFunction('uri', function ($action, $parameters = [], $prefixAction = null) use ($app) {
            return $app['alias_resolver']->generateUrl($action, $parameters, $prefixAction);
        }));

        return $twig;
    }

    public function boot(Application $app)
    {
    }

    protected function initializeCache(Twig_Environment $twig, Application $app)
    {
        $cacheProvider = new CacheProvider($app['cache']);
        $keyGenerator = new KeyGenerator($app['locale']);

        $cacheStrategy = new GenerationalCacheStrategy($cacheProvider, $keyGenerator, 0);
        $cacheExtension = new CacheExtension($cacheStrategy);

        $twig->addExtension($cacheExtension);
    }

    public function torrentStateToClass($torrent)
    {
        $state = $torrent['state'];

        if ($state === 'paused') {
            $class = 'warning';
        } elseif ($state === 'seeding' || $state === 'downloading') {
            $class = 'success';
        } else {
            $class = 'info';
        }

        return $class;
    }

    protected function includeResource($relativePath, $inline)
    {
        $absolutePath = WEB . DIRECTORY_SEPARATOR . $relativePath;

        if ($inline === true || ($inline === null && filesize($absolutePath) < 1024)) {
            return ['content' => file_get_contents($absolutePath)];
        }

        return ['path' => '//' . STATIC_HOST . '/' . $relativePath];
    }

    public function includeCss($path, $inline = null)
    {
        $result = $this->includeResource($path . '.css', $inline);

        if (isset($result['content'])) {
            return '<style type="text/css">' . $result['content'] . '</style>';
        }

        return '<link rel="stylesheet" type="text/css" href="' . $result['path'] . '" />';
    }

    public function includeJs($path, $inline = null)
    {
        $result = $this->includeResource($path . '.js', $inline);

        if (isset($result['content'])) {
            return '<script type="text/javascript">' . $result['content'] . '</script>';
        }

        return '<script type="text/javascript" src="' . $result['path'] . '"></script>';
    }
}