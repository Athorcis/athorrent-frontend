<?php

namespace Athorrent\Cache\KeyGenerator;

use Symfony\Component\HttpFoundation\RequestStack;

class LocalizedKeyGenerator extends KeyGenerator
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function generateKey($value): string
    {
        if ($value === null) {
            $keySuffix = '';
        } else {
            $keySuffix = '.' . parent::generateKey($value);
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request->getLocale() . $keySuffix;
    }
}
