<?php

namespace Athorrent\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login_check", methods="POST", name="login_check")
     */
    public function loginCheck(): void
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Route("/logout", methods="GET", name="logout")
     */
    public function logout(): void
    {

    }
}
