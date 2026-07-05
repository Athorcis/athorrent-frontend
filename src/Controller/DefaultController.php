<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Athorrent\Form\Type\LoginType;
use Athorrent\View\View;
use Athorrent\View\ViewType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route(path: '/', methods: 'GET')]
    public function home(): View
    {
        return new View(ViewType::Page);
    }

    #[Route(path: '/login', methods: 'GET')]
    public function login(Request $request): Response|View
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('files_listFiles');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        return new View(ViewType::Page, ['form' => $form]);
    }
}
