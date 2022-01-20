<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration", name="administration")
 */
class AdministrationController extends AbstractController
{
    /**
     * @Route("/", methods="GET")
     */
    public function listAdministrationModules(): View
    {
        return new View([], 'administration');
    }
}
