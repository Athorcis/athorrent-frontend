<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Silex\Application;
use Symfony\Component\Routing\Annotation\Route;

class CacheController
{
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
    public function clearApc(Application $app)
    {
        if (!$app['cache.cleaner']->clearApplicationCache()) {
            throw new \Exception('unable to clear application cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/twig", options={"expose"=true})
     */
    public function clearTwig(Application $app)
    {
        if (!$app['cache.cleaner']->clearTwigCache()) {
            throw new \Exception('unable to clear twig cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/translations", options={"expose"=true})
     */
    public function clearTranslations(Application $app)
    {
        if (!$app['cache.cleaner']->clearTranslationsCache()) {
            throw new \Exception('unable to clear translation cache');
        }

        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/", options={"expose"=true})
     */
    public function clearAll(Application $app)
    {
        $this->clearApc($app);

        $this->clearTwig($app);

        $this->clearTranslations($app);

        return [];
    }
}
