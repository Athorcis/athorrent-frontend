<?php

namespace Athorrent\Filesystem;

abstract class AbstractFilesystem implements FilesystemInterface
{
    public function __construct(protected string $root)
    {
    }

    public function getRoot(): string
    {
        return $this->root;
    }
}
