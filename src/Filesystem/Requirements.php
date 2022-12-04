<?php

namespace Athorrent\Filesystem;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Requirements
{
    public function __construct(
        readonly bool $path = false,
        readonly bool $dir = false,
        readonly bool $file = false,
    ) {}
}
