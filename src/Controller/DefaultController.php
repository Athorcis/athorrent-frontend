<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController
{
    /**
     * @Method("GET")
     * @Route("/")
     */
    public function home()
    {
        return new View();
    }
}
