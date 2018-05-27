<?php

namespace Athorrent\Controller;

use Athorrent\Cache\CacheCleaner;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/cache", name="cache")
 */
class CacheController
{
    protected $cacheCleaner;

    public function __construct(CacheCleaner $cacheCleaner)
    {
        $this->cacheCleaner = $cacheCleaner;
    }

    /**
     * @Method("GET")
     * @Route("/")
     */
    public function handleCache()
    {
        return new View([], 'cache');
    }

    /**
     * @Method("DELETE")
     * @Route("/apc", options={"expose"=true})
     */
    public function clearApc()
    {
        if (!$this->cacheCleaner->clearApplicationCache()) {
            throw new \Exception('unable to clear application cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/twig", options={"expose"=true})
     */
    public function clearTwig()
    {
        if (!$this->cacheCleaner->clearTwigCache()) {
            throw new \Exception('unable to clear twig cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/translations", options={"expose"=true})
     */
    public function clearTranslations()
    {
        if (!$this->cacheCleaner->clearTranslationsCache()) {
            throw new \Exception('unable to clear translation cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/", options={"expose"=true})
     */
    public function clearAll()
    {
        $this->clearApc();

        $this->clearTwig();

        $this->clearTranslations();

        return [];
    }
}
