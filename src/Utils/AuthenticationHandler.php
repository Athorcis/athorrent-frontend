<?php

namespace Athorrent\Utils;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationHandler implements AuthenticationFailureHandlerInterface {
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        $request->getSession()->getFlashBag()->add('error', 'error.loginFailure');
        return new RedirectResponse($request->headers->get('referer'));
    }
}

?>
