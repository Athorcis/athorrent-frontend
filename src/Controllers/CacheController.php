<?php

namespace Athorrent\Controllers;

use Athorrent\Routing\AbstractController;
use Athorrent\Cache\CacheUtils;
use Athorrent\View\View;

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

    public function clearApc()
    {
        if (!$app['cache.cleaner']->cleanApplicationCache()) {
            throw new \Exception('unable to clear application cache');
        }

        return [];
    }

    public function clearTwig()
    {
        if (!$app['cache.cleaner']->cleanTwigCache()) {
            throw new \Exception('unable to clear twig cache');
        }

        return [];
    }

    public function clearTranslations()
    {
        if (!$app['cache.cleaner']->cleanTranslationsCache()) {
            throw new \Exception('unable to clear translation cache');
        }

        return [];
    }

    public function clearAll()
    {
        $this->clearApc();

        $this->clearTwig();

        $this->clearTranslations();

        return [];
    }
}
