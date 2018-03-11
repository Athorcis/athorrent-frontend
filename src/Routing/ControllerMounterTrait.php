<?php

namespace Athorrent\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

trait ControllerMounterTrait
{
    abstract public function getControllers();

    protected function addRoute(RouteCollection $routes, $prefix, $controller, $prefixId, $method, $pattern, $action, $type)
    {
        if ($type === 'both') {
            $this->addRoute($routes, $prefix, $controller, $prefixId, $method, $pattern, $action, 'ajax');
        }

        $path = rtrim($prefix . $pattern, '/');
        $name = $prefixId . '.' . $action;

        if ($type === 'ajax') {
            $path = '/ajax' . $path;
            $name = 'ajax|' . $name;
        }

        $route = new Route($path, [
            '_action' => $action,
            '_ajax' => $type === 'ajax',
            '_controller' => get_class($controller) . '::' . $action,
            '_prefixId' => $prefixId
        ]);


        $route->setMethods($method);

        $i18nRoute = clone $route;

        $i18nRoute->setPath('/{_locale}' . $path);
        $i18nRoute->setRequirement('_locale', 'en|fr');

        $routes->add('i18n|' . $name, $i18nRoute);

        $route->setDefault('_locale', 'fr');
        $routes->add($name, $route);
    }

    protected function buildRouteCollection(RouteCollection $routes)
    {
        $controllerDescriptors = $this->getControllers();

        foreach ($controllerDescriptors as $controllerDescriptor) {
            list($prefix, $controller, $prefixId) = $controllerDescriptor;

            foreach ($controller->getRouteDescriptors() as $descriptor) {
                list($method, $pattern, $action) = $descriptor;
                $type = isset($descriptor[3]) ? $descriptor[3] : '';

                $this->addRoute($routes, $prefix, $controller, $prefixId, $method, $pattern, $action, $type);
            }
        }

        return $routes;
    }

    public function flush()
    {
        $cache = $this['cache'];

        if ($cache->has('routes')) {
            $routes = $cache->get('routes');
        } else {
            $routes = $this['controllers']->flush();
            $this->buildRouteCollection($routes);

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
}
