<?php

namespace Athorrent\Controllers;

use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [['GET', '/', 'home']];
    }

    public function home(Request $request)
    {
        return $this->render();
    }
}
