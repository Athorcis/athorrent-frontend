<?php

namespace Athorrent\Security\Nonce;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NonceManager extends AbstractExtension
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
            new TwigFunction('csp_nonce', [$this, 'getNonce']),
        ];
    }
}
