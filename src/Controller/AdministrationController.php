<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/administration', name: 'administration')]
class AdministrationController extends AbstractController
{
    #[Route(path: '/', methods: 'GET')]
    public function listAdministrationModules(): View
    {
        return new View([], 'administration');
    }
}
