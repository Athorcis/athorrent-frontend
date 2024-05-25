<?php

namespace Athorrent\Cache\KeyGenerator;

interface KeyGeneratorInterface
{
    public function generateKey($value): string;
}
