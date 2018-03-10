<?php

namespace Athorrent\Cache;

interface CachableInterface
{
    public function getCacheKey(): string;
}
