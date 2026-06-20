<?php

declare(strict_types=1);

namespace Athorrent\Cache\KeyGenerator;

interface CacheKeyGetterInterface
{
    public function getCacheKey(): string;
}
