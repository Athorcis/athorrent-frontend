<?php

namespace Athorrent\Controller;

use Athorrent\Routing\AbstractController;
use Athorrent\Cache\CacheUtils;
use Athorrent\View\View;
use Silex\Application;

class CacheController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'handleCache'],

            ['POST', '/clear/apc', 'clearApc', 'ajax'],
            ['POST', '/clear/twig', 'clearTwig', 'ajax'],
            ['POST', '/clear/translations', 'clearTranslations', 'ajax'],
            ['POST', '/clear', 'clearAll', 'ajax']
        ];
    }

    public function handleCache()
    {
        return new View([], 'cache');
    }

    public function clearApc(Application $app)
    {
        if (!$app['cache.cleaner']->clearApplicationCache()) {
            throw new \Exception('unable to clear application cache');
        }

        return [];
    }

    public function clearTwig(Application $app)
    {
        if (!$app['cache.cleaner']->clearTwigCache()) {
            throw new \Exception('unable to clear twig cache');
        }

        return [];
    }

    public function clearTranslations(Application $app)
    {
        if (!$app['cache.cleaner']->clearTranslationsCache()) {
            throw new \Exception('unable to clear translation cache');
        }

        return [];
    }

    public function clearAll(Application $app)
    {
        $this->clearApc($app);

        $this->clearTwig($app);

        $this->clearTranslations($app);

        return [];
    }
}
