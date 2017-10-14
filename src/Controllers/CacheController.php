<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\CacheUtils;
use Symfony\Component\HttpFoundation\Request;

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

    public function handleCache(Request $request)
    {
        return $this->render(array(), 'cache');
    }

    public function clearApc(Request $request)
    {
        if (CacheUtils::clearApc()) {
            return $this->success();
        }
    }

    public function clearTwig(Request $request)
    {
        if (CacheUtils::clearTwig()) {
            return $this->success();
        }
    }

    public function clearTranslations(Request $request)
    {
        if (CacheUtils::clearTranslations()) {
            return $this->success();
        }
    }

    public function clearAll(Request $request)
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
