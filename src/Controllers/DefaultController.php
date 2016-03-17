<?php

namespace Athorrent\Controllers;

use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    protected static function buildRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'home');

        return $routes;
    }

    public function home(Request $request)
    {
        return $this->render();
    }
}
