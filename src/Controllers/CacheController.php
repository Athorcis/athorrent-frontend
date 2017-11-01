<?php

namespace Athorrent\Controllers;

use Athorrent\Routing\AbstractController;
use Athorrent\Utils\CacheUtils;
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
        if (!CacheUtils::clearApc()) {
            throw new \Exception('unable to clear apc cache');
        }

        return [];
    }

    public function clearTwig()
    {
        if (!CacheUtils::clearTwig()) {
            throw new \Exception('unable to clear twig cache');
        }

        return [];
    }

    public function clearTranslations()
    {
        if (!CacheUtils::clearTranslations()) {
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
