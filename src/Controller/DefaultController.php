<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController
{
    /**
     * @Route("/", methods="GET")
     */
    public function home(): View
    {
        return new View();
    }
}
