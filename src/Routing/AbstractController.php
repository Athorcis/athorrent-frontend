<?php

namespace Athorrent\Routing;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

abstract class AbstractController implements ControllerProviderInterface
{
    abstract protected function getRouteDescriptors();

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $class = get_called_class();

        foreach ($this->getRouteDescriptors() as $descriptor) {
            list($method, $pattern, $action) = $descriptor;
            $type = isset($descriptor[3]) ? $descriptor[3] : '';

            $controllers->addController($controllers, $class . '::' . $action, $method, $pattern, $action, $type);
        }

        return $controllers;
    }
}
