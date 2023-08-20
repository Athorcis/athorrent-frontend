<?php

namespace Athorrent\Filesystem;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class Requirements
{
    public function __construct(
        public bool $path = false,
        public bool $dir = false,
        public bool $file = false,
    ) {}
}
