<?php

namespace Athorrent\Controller;

use Athorrent\Cache\CacheCleaner;
use Athorrent\View\View;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/cache", name="cache")
 */
class CacheController extends AbstractController
{
    protected CacheCleaner $cacheCleaner;

    public function __construct(CacheCleaner $cacheCleaner)
    {
        $this->cacheCleaner = $cacheCleaner;
    }

    /**
     * @Route("/", methods="GET")
     */
    public function handleCache(): View
    {
        return new View([], 'cache');
    }

    /**
     * @Route("/apc", methods="DELETE", options={"expose"=true})
     */
    public function clearApc(): array
    {
        if (!$this->cacheCleaner->clearApplicationCache()) {
            throw new RuntimeException('unable to clear application cache');
        }

        return [];
    }

    /**
     * @Route("/twig", methods="DELETE", options={"expose"=true})
     */
    public function clearTwig(): array
    {
        if (!$this->cacheCleaner->clearTwigCache()) {
            throw new RuntimeException('unable to clear twig cache');
        }

        return [];
    }

    /**
     * @Route("/translations", methods="DELETE", options={"expose"=true})
     */
    public function clearTranslations(): array
    {
        if (!$this->cacheCleaner->clearTranslationsCache()) {
            throw new RuntimeException('unable to clear translation cache');
        }

        return [];
    }

    /**
     * @Route("/", methods="DELETE", options={"expose"=true})
     */
    public function clearAll(): array
    {
        $this->clearApc();

        $this->clearTwig();

        $this->clearTranslations();

        return [];
    }
}
