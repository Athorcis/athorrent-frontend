<?php

namespace Athorrent\Filesystem;

abstract class AbstractFilesystem implements FilesystemInterface
{
    /** @var string */
    protected $root;

    public function __construct(string $root)
    {
        $this->root = $root;
    }

    public function getRoot(): string
    {
        return $this->root;
    }
}
