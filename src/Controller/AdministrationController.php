<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration", name="administration")
 */
class AdministrationController
{
    /**
     * @Route("/", methods="GET")
     */
    public function listAdministrationModules(): View
    {
        return new View([], 'administration');
    }
}
