<?php

namespace Athorrent\Controller;

use Athorrent\Routing\AbstractController;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration", name="administration")
 */
class AdministrationController
{
    /**
     * @Method("GET")
     * @Route("/")
     */
    public function listAdministrationModules()
    {
        return new View([], 'administration');
    }
}
