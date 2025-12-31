<?php

namespace Athorrent\Controller;

use Athorrent\View\View;
use Athorrent\View\ViewType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route(path: '/', methods: 'GET')]
    public function home(): View
    {
        return new View(ViewType::Page);
    }
}
