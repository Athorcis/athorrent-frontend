<?php

namespace Athorrent\Routing;

use Silex\Controller;
use Silex\ControllerCollection as BaseControllerCollection;
use Silex\Route;
use Symfony\Component\Routing\RouteCollection;

class ControllerCollection extends BaseControllerCollection
{
    protected $defaultLocale;

    protected $locales;

    protected $prefixId;

    public function __construct(Route $defaultRoute, $defaultLocale, array $locales)
    {
        parent::__construct($defaultRoute);

        $this->defaultLocale = $defaultLocale;
        $this->locales = $locales;
    }

    public function mount($prefix, $controllers, $prefixId = null)
    {
        parent::mount($prefix, $controllers);
        $controllers->prefixId = $prefixId;
    }

    public function addController(self $controllers, $callback, $method, $pattern, $action, $type)
    {
        $controller = $controllers->match($pattern, $callback);
        $controller->method($method);
        $controller->bind($action);

        $route = $controller->getRoute();
        $route->setDefault('_action', $action);
        $route->setDefault('_ajax', $type === 'ajax');

        if ($type === 'both') {
            $this->addController($controllers, $callback, $method, $pattern, $action, 'ajax');
        }
    }

    public function flush()
    {
        return $this->doFlush('', new RouteCollection());
    }

    protected function getLocalePrefixedPaths($path)
    {
        $paths = [];

        foreach ($this->locales as $locale) {
            if ($this->defaultLocale === $locale) {
                $paths[$locale] = $path;
            } else {
                $paths[$locale] = '/' . $locale . $path;
            }
        }

        return $paths;
    }

    protected function doFlush($prefix, RouteCollection $routes)
    {
        $prefix = trim(trim($prefix), '/');

        if ($prefix !== '') {
            $prefix = '/' . $prefix;
        }

        foreach ($this->controllers as $controller) {
            if ($controller instanceof Controller) {
                $route = $controller->getRoute();

                $path = $prefix . $route->getPath();
                $name = $controller->getRouteName();

                if (!$name) {
                    $name = $controller->generateRouteName($prefix);
                }

                if (isset($this->prefixId)) {
                    $route->setDefault('_prefixId', $this->prefixId);

                    $name = $this->prefixId . '.' . $name;

                    if ($route->getDefault('_ajax')) {
                        $path = '/ajax' . $path;
                        $name = 'ajax|' . $name;
                    }
                }

                $route->setPath($path);
                $controller->bind($name);

                if ($name[0] === '_') {
                    $routes->add($name, $route);
                } else {
                    foreach ($this->getLocalePrefixedPaths($path) as $locale => $path) {
                        $localeRoute = clone $route;

                        $localeRoute->setPath($path);
                        $localeRoute->setDefault('_locale', $locale);

                        $routes->add($locale . '|' . $name, $localeRoute);
                    }
                }

                $controller->freeze();
            } else {
                $controller->doFlush($prefix . $controller->prefix, $routes);
            }
        }

        return $routes;
    }
}
