<?php

namespace Athorrent\Controller;

use Athorrent\Routing\AbstractController;
use Athorrent\View\View;

class DefaultController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [['GET', '/', 'home']];
    }

    public function home()
    {
        return new View();
    }
}
