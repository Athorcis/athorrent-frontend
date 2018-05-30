<?php

namespace Athorrent\Security\Nonce;

class NonceManager extends \Twig_Extension
{
    private $nonce;

    protected function generateNonce()
    {
        return bin2hex(random_bytes(16));
    }

    public function getNonce()
    {
        if (!$this->nonce) {
            $this->nonce = $this->generateNonce();
        }

        return $this->nonce;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('csp_nonce', [$this, 'getNonce']),
        ];
    }
}
