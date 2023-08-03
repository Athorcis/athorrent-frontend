<?php

namespace Athorrent\Cache\KeyGenerator;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

class LocalizedKeyGenerator extends KeyGenerator
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function generateKey($value): string
    {
        if ($value === null) {
            $keySuffix = '';
        } else {
            $keySuffix = '.' . parent::generateKey($value);
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException('cannot generated localized key without a request');
        }

        return $request->getLocale() . $keySuffix;
    }
}
