<?php

namespace Athorrent\Utils\CSRF;

use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class TokenGenerator implements TokenGeneratorInterface
{
    private $sessionId;

    private $salt;

    public function __construct($sessionId, $salt)
    {
        $this->sessionId = $sessionId;
        $this->salt = $salt;
    }

    public function generateToken()
    {
        return hash('sha512', $this->sessionId . time() . $this->salt);
    }
}
