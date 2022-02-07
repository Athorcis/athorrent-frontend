<?php

namespace Athorrent\Filesystem;

abstract class AbstractFilesystem implements FilesystemInterface
{
    protected string $root;

    public function __construct(string $root)
    {
        $this->root = $root;
    }

    public function getRoot(): string
    {
        return $this->root;
    }
}
