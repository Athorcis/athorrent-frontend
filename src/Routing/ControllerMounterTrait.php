<?php

namespace Athorrent\Routing;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;

trait ControllerMounterTrait
{
    public function flush()
    {
        $cache = $this['cache'];

        if ($cache->has('routes')) {
            $routes = $cache->get('routes');
        } else {
            $routes = $this['controllers']->flush();

            $directoryLoader = new AnnotationDirectoryLoader(new FileLocator(__DIR__ . '/../Controller'), new RouteLoader(new AnnotationReader(), $this['locales'], 'fr'));
            $routes->addCollection($directoryLoader->load('.'));

            $cache->set('routes', $routes);
            $this['request_matcher_cache']->storeRequestMatcher($routes);
        }

        $this['routes']->addCollection($routes);

         if ($cache->has('action_map')) {
             $actionMap = $cache->get('action_map');
        } else {
             $actionMap = new ActionMap($routes);
             $cache->set('action_map', $actionMap);
        }

        $this['action_map']->buildActionMap();
    }
}
