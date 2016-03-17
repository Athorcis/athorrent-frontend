<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\CacheUtils;
use Symfony\Component\HttpFoundation\Request;

class CacheController extends AbstractController
{
    protected static $actionPrefix = 'cache_';

    protected static $routePattern = '/administration/cache';

    protected static function buildRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'handleCache');

        return $routes;
    }

    protected static function buildAjaxRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = array('POST', '/clear/apc', 'clearApc');
        $routes[] = array('POST', '/clear/twig', 'clearTwig');
        $routes[] = array('POST', '/clear/translations', 'clearTranslations');
        $routes[] = array('POST', '/clear', 'clearAll');

        return $routes;
    }

    protected function handleCache(Request $request)
    {
        return $this->render(array(), 'cache');
    }

    protected function clearApc(Request $request)
    {
        if (CacheUtils::clearApc()) {
            return $this->success();
        }
    }

    protected function clearTwig(Request $request)
    {
        if (CacheUtils::clearTwig()) {
            return $this->success();
        }
    }

    protected function clearTranslations(Request $request)
    {
        if (CacheUtils::clearTranslations()) {
            return $this->success();
        }
    }

    protected function clearAll(Request $request)
    {
        if (!CacheUtils::clearApc()) {
            return $this->abort();
        }

        if (!CacheUtils::clearTwig()) {
            return $this->abort();
        }

        if (!CacheUtils::clearTranslations()) {
            return $this->abort();
        }

        return $this->success();
    }
}
