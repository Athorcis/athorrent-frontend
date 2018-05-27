<?php

namespace Athorrent\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends Controller
{
    /**
     * @Method("POST")
     * @Route("/login_check", name="login_check")
     */
    public function loginCheck()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Method("GET")
     * @Route("/logout", name="logout")
     */
    public function logout()
    {

    }
}
