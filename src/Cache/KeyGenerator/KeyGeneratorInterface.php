<?php

declare(strict_types=1);

namespace Athorrent\Cache\KeyGenerator;

interface KeyGeneratorInterface
{
    /**
     * @param CacheKeyGetterInterface|list<string>|string $value
     */
    public function generateKey(CacheKeyGetterInterface|array|string $value): string;
}
