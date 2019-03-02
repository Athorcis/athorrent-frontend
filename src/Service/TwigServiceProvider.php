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
    private $manifest;

    public function register(Application $app)
    {
        $app->register(new \Silex\Provider\TwigServiceProvider(), [
            'twig.path' => TEMPLATES_DIR,
            'twig.options' => ['cache' => CACHE_DIR . DIRECTORY_SEPARATOR . 'twig']
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

        $twig->addFunction(new Twig_SimpleFunction('asset_path', [$this, 'getAssetPath']));

        $twig->addFunction(new Twig_SimpleFunction('asset_url', [$this, 'getAssetUrl']));

        $twig->addFunction(new Twig_SimpleFunction('stylesheet', [$this, 'includeStylesheet']));

        $twig->addFunction(new Twig_SimpleFunction('script', [$this, 'includeScript']));

        $twig->addFunction(new Twig_SimpleFunction('format_age', [$this, 'formatAge']));

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
        $this->manifest = json_decode(file_get_contents(WEB_DIR . DIRECTORY_SEPARATOR . 'manifest.json'), true);
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
        } elseif ($state === 'disabled') {
            $class = 'disabled';
        } else {
            $class = 'info';
        }

        return $class;
    }

    public function getAssetPath($assetId)
    {
        if (isset($this->manifest[$assetId])) {
            return $this->manifest[$assetId];
        }

        return '/' . $assetId;
    }

    public function getAssetUrl($assetId)
    {
        return '//' . STATIC_HOST . $this->getAssetPath($assetId);
    }

    protected function includeResource($assetId, $inline)
    {
        $relativePath = $this->getAssetPath($assetId);
        $absolutePath = WEB_DIR . $relativePath;

        if (!DEBUG && ($inline === true || ($inline === null && filesize($absolutePath) < 1024))) {
            return ['content' => file_get_contents($absolutePath)];
        }

        return ['path' => '//' . STATIC_HOST . $relativePath];
    }

    public function includeStylesheet($path, $inline = false)
    {
        $result = $this->includeResource('stylesheets/' . $path . '.css', $inline);

        if (isset($result['content'])) {
            return '<style type="text/css">' . $result['content'] . '</style>';
        }

        return '<link rel="stylesheet" type="text/css" href="' . $result['path'] . '" />';
    }

    public function includeScript($path, $inline = false)
    {
        $result = $this->includeResource('scripts/' . $path . '.js', $inline);

        if (isset($result['content'])) {
            return '<script type="text/javascript">' . $result['content'] . '</script>';
        }

        return '<script type="text/javascript" src="' . $result['path'] . '"></script>';
    }

    public function formatAge($age)
    {
        global $app;

        $steps = [
            'seconds' => 60,
            'minutes' => 3600,
            'hours' => 86400,
            'days' => 2592000,
            'months' => 31557600,
            'years' => INF
        ];

        $previousLimit = 1;

        foreach ($steps as $magnitude => $limit) {
            if ($age < $limit) {
                $n = floor($age / $previousLimit);
                return $n . ' ' . $app['translator']->transChoice('search.age.' . $magnitude, $n);
            }

            $previousLimit = $limit;
        }
    }
}