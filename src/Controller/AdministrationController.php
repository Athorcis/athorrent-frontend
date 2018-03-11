<?php

namespace Athorrent\Controller;

use Athorrent\Routing\AbstractController;
use Athorrent\View\View;

class AdministrationController extends AbstractController
{
    public function getRouteDescriptors()
    {
        return [['GET', '/', 'listAdministrationModules']];
    }

    public function listAdministrationModules()
    {
        return new View([], 'administration');
    }
}
