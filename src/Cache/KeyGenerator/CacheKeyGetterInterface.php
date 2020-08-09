<?php

namespace Athorrent\Cache\KeyGenerator;

interface CacheKeyGetterInterface
{
    public function getCacheKey(): string;
}
