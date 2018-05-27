<?php

namespace Athorrent\Cache\Twig;

/**
 * Generates a key for a given value.
 */
interface KeyGeneratorInterface
{
    /**
     * Generate a cache key for a given value.
     * @param mixed $value
     * @return string
     */
    public function generateKey($value);
}
