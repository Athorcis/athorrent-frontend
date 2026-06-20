<?php

declare(strict_types=1);

namespace Athorrent\Cache\KeyGenerator;

interface KeyGeneratorInterface
{
    public function generateKey($value): string;
}
