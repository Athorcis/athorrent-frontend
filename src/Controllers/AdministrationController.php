<?php

namespace Athorrent\Controllers;

use Symfony\Component\HttpFoundation\Request;

class AdministrationController extends AbstractController
{
    protected static $actionPrefix = 'administration_';

    protected static $routePattern = '/administration';

    protected static function buildRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'listAdministrationModules');

        return $routes;
    }

    protected function listAdministrationModules(Request $request)
    {
        return $this->render(array(), 'administration');
    }
}
