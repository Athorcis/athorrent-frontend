<?php

namespace Athorrent\Routing;


class Router extends \Symfony\Bundle\FrameworkBundle\Routing\Router
{
    protected $actionMap;

    public function getActioMap()
    {
        if ($this->actionMap === null) {
            $this->actionMap = new ActionMap($this->getRouteCollection());
            $this->actionMap->buildActionMap();
        }

        return $this->actionMap;
    }

    protected function getGeneratorDumperInstance()
    {
        return new PhpGeneratorDumper($this->getRouteCollection(), $this->getActioMap());
    }
}
