<?php

namespace Athorrent\Routing;

use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;

trait ControllerMounterTrait
{
    abstract public function mountControllers();

    public function flush()
    {
        $cache = $this['cache'];
        $locale = $this['locale'];

        if ($cache->has('routes')) {
            $routes = $cache->get('routes');
        } else {
            $this->mountControllers();
            $routes = $this['controllers']->flush();

            $cache->set('routes', $routes);
            $this['request_matcher_cache']->storeRequestMatcher($routes);
        }

        $this['routes']->addCollection($routes);

         if ($cache->has('action_map')) {
             $actionMap = $cache->get('action_map');
        } else {
            $actionMap = [];

            foreach ($routes as $route) {
                if ($route->hasDefault('_action')) {
                    $action = $route->getDefault('_action');

                    if (!$route->getDefault('_ajax')) {
                        $actionMap[$action][] = $route->getDefault('_prefixId');
                    }
                }
            }

            foreach ($actionMap as &$prefixIds) {
                $prefixIds = array_unique($prefixIds);
            }

            $cache->set('action_map', $actionMap);
        }

        $this['url_generator']->setActionMap($actionMap);
    }

    public function mount($prefix, $controllers, $prefixId = null)
    {
        if ($controllers instanceof ControllerProviderInterface) {
            $connectedControllers = $controllers->connect($this);

            if (!$connectedControllers instanceof ControllerCollection) {
                throw new \LogicException(sprintf('The method "%s::connect" must return a "ControllerCollection" instance. Got: "%s"', get_class($controllers), is_object($connectedControllers) ? get_class($connectedControllers) : gettype($connectedControllers)));
            }

            $controllers = $connectedControllers;
        } elseif (!$controllers instanceof ControllerCollection && !is_callable($controllers)) {
            throw new \LogicException('The "mount" method takes either a "ControllerCollection" instance, "ControllerProviderInterface" instance, or a callable.');
        }

        $this['controllers']->mount($prefix, $controllers, $prefixId);

        return $this;
    }
}
