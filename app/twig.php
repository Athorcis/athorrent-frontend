<?php

use Asm89\Twig\CacheExtension\CacheStrategy\GenerationalCacheStrategy;
use Asm89\Twig\CacheExtension\Extension as CacheExtension;
use Athorrent\Utils\Cache\CacheProvider;
use Athorrent\Utils\Cache\KeyGenerator;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use SPE\FilesizeExtensionBundle\Twig\FilesizeExtension;
use Twig_Environment;

function initializeTwig(Application $app) {
    $app->register(new TwigServiceProvider(), array (
        'twig.path' => APP . '/views',
        'twig.options' => array (
            'cache' => CACHE . '/twig'
        )
    ));

    $app['twig'] = $app->share($app->extend('twig', function(Twig_Environment $twig, $app) {
        $cacheProvider = new CacheProvider($app['cache']);
        $cacheStrategy  = new GenerationalCacheStrategy($cacheProvider, new KeyGenerator(), 0);
        $cacheExtension = new CacheExtension($cacheStrategy);

        $twig->addExtension($cacheExtension);
        $twig->addExtension(new FilesizeExtension());

        $twig->addFunction(new Twig_SimpleFunction('torrentStateToClass', function ($torrent) {
            if ($torrent['state'] === 'paused') {
                $class = 'warning';
            } else if ($torrent['state'] === 'seeding' || $torrent['state'] === 'downloading') {
                $class = 'success';
            } else {
                $class = 'info';
            }

            return $class;
        }));

        function twigIncludeStatic($internalPath, $externalPath) {
            $absolutePath = WEB . DIRECTORY_SEPARATOR . $internalPath;

            if (filesize($absolutePath) < 1024) {
                return array('content' => file_get_contents($absolutePath));
            }

            return array('path' => '//' . STATIC_HOST . '/' . $externalPath);
        }

        function twigIncludeCss($path) {
            $path = $path . '.css';
            $result = twigIncludeStatic($path, $path);

            if (isset($result['content'])) {
                return '<style type="text/css">' . $result['content'] . '</style>';
            }

            return '<link rel="stylesheet" type="text/css" href="' . $result['path'] . '" />';
        }

        function twigIncludeJs($path) {
            $internalPath = $path . '.js';

            if (DEBUG) {
                $externalPath = $internalPath;
            } else {
                require_once WEB . '/minify.php';

                $internalPath = str_replace(WEB . DIRECTORY_SEPARATOR, '', getMinifiedJs($internalPath));
                $externalPath = $path . '.min.js';
            }

            $result = twigIncludeStatic($internalPath, $externalPath);

            if (isset($result['content'])) {
                return '<script type="text/javascript">' . $result['content'] . '</script>';
            }

            return '<script type="text/javascript" src="' . $result['path'] . '"></script>';
        }

        $twig->addFunction(new Twig_SimpleFunction('css', function ($path) {
            return twigIncludeCss($path);
        }));

        $twig->addFunction(new Twig_SimpleFunction('js', function ($path) {
            return twigIncludeJs($path);
        }));

        $twig->addFunction(new Twig_SimpleFunction('path', function ($action, $parameters = array(), $prefixAction = null) use($app) {
            return $app['alias_resolver']->generatePath($action, $parameters, $prefixAction);
        }));

        $twig->addFunction(new Twig_SimpleFunction('uri', function ($action, $parameters = array(), $prefixAction = null) use($app) {
            return $app['alias_resolver']->generateUrl($action, $parameters, $prefixAction);
        }));

        return $twig;
    }));
}

?>
