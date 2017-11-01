<?php

namespace Athorrent\Routing;

use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;

trait ControllerMounterTrait
{
    public abstract function mountControllers();

    public function flush()
    {
        $cache = $this['cache'];
        $locale = $this['locale'];

//        if ($cache->contains('routes')) {
//            $routes = $cache->fetch('routes');
//        } else {
            $this->mountControllers();
            $routes = $this['controllers']->flush();

//            $cache->save('routes', $routes);
//        }

        $this['routes']->addCollection($routes);

        $ajaxRouteDescriptorsKey = 'ajax_route_descriptors_' . $locale;

//        if ($cache->contains($ajaxRouteDescriptorsKey) && $cache->contains('action_map')) {
//            $this['ajax_route_descriptors'] = $cache->fetch($ajaxRouteDescriptorsKey);
//            $this['url_generator']->setActionMap($cache->fetch('action_map'));
//        } else {
            $ajaxRouteDescriptors = [];
            $actionMap = [];

            foreach ($routes as $route) {
                if ($route->hasDefault('_action')) {
                    $action = $route->getDefault('_action');
                    $prefixId = $route->getDefault('_prefixId');

                    if ($route->getDefault('_ajax')) {
                        if ($route->getDefault('_locale') === $locale) {
                            $ajaxRouteDescriptors[$action][$prefixId] = [
                                $route->getMethods()[0],
                                $route->getPath()
                            ];
                        }
                    } else {
                        $actionMap[$action][] = $prefixId;
                    }
                }
            }

            foreach ($actionMap as &$prefixIds) {
                $prefixIds = array_unique($prefixIds);
            }

            $this['ajax_route_descriptors'] = $ajaxRouteDescriptors;
            $this['url_generator']->setActionMap($actionMap);

//            $cache->save($ajaxRouteDescriptorsKey, $ajaxRouteDescriptors);
//            $cache->save('action_map', $actionMap);
//        }
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
