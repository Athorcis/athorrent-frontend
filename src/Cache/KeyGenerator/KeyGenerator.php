<?php

declare(strict_types=1);

namespace Athorrent\Cache\KeyGenerator;

use InvalidArgumentException;

class KeyGenerator implements KeyGeneratorInterface
{
    /**
     * @param CacheKeyGetterInterface|string[]|string $value
     */
    public function generateKey(CacheKeyGetterInterface|array|string $value): string
    {
        if ($value instanceof CacheKeyGetterInterface) {
           return $value->getCacheKey();
        }
        
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
