<?php

namespace Athorrent\Utils\CSRF;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class TokenManager
{
    private $sessionId;

    private $csrfTokenManager;

    public function __construct(SessionInterface $session)
    {
        $this->sessionId = $session->getId();
        $this->csrfTokenManager = new CsrfTokenManager(new TokenGenerator($this->sessionId, CSRF_SALT), new SessionTokenStorage($session));
    }

    public function getToken()
    {
        return $this->csrfTokenManager->getToken($this->sessionId)->getValue();
    }

    public function refreshToken()
    {
        return $this->csrfTokenManager->refreshToken($this->sessionId)->getValue();
    }

    public function isTokenValid($token)
    {
        return $this->csrfTokenManager->isTokenValid(new CsrfToken($this->sessionId, $token));
    }
}
