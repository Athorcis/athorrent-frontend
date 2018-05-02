<?php

namespace Athorrent\Routing;


use Symfony\Component\Routing\RouteCollection;

class ActionMap implements \ArrayAccess, \Iterator
{
    private $routes;

    private $actionMap;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function buildActionMap()
    {
        $actionMap = [];

        foreach ($this->routes as $route) {
            if ($route->hasDefault('_action')) {
                $action = $route->getDefault('_action');
                $actionMap[$action][] = $route->getDefault('_prefixId');
            }
        }

        foreach ($actionMap as &$prefixIds) {
            $prefixIds = array_unique($prefixIds);
        }

        $this->actionMap = $actionMap;
    }

    public function offsetExists($offset)
    {
        return isset($this->actionMap[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->actionMap[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->actionMap[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->actionMap[$offset]);
    }

    public function current()
    {
        return current($this->actionMap);
    }

    public function next()
    {
        next($this->actionMap);
    }


    public function key()
    {
        return key($this->actionMap);
    }

    public function valid()
    {
        return key($this->actionMap) !== null;
    }

    public function rewind()
    {
        reset($this->actionMap);
    }
}
