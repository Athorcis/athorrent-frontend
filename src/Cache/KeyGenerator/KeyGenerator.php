<?php

namespace Athorrent\Cache\KeyGenerator;

use InvalidArgumentException;

class KeyGenerator implements KeyGeneratorInterface
{
    public function generateKey($value): string
    {
        if ($value instanceof CacheKeyGetterInterface) {
            $key = $value->getCacheKey();
        } elseif (is_array($value)) {
            $key = implode(',', $value);
        } elseif (is_string($value)) {
            $key = $value;
        } else {
            throw new InvalidArgumentException(sprintf('unable to convert object of type %s to cache key', $value::class));
        }

        return $key;
    }
}
