<?php

namespace Athorrent\Controllers;

use Symfony\Component\HttpFoundation\Request;

class AdministrationController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [['GET', '/', 'listAdministrationModules']];
    }

    public function listAdministrationModules(Request $request)
    {
        return $this->render(array(), 'administration');
    }
}
