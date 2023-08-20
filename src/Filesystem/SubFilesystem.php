<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;

class SubFilesystem extends AbstractFilesystem
{
    private readonly Filesystem $internalFilesystem;

    public function __construct(string $root)
    {
        parent::__construct($root);
        $this->internalFilesystem = new Filesystem('/');
    }

    public function getSize(string $path): int
    {
        return $this->internalFilesystem->getSize($this->getInternalPath($path));
    }

    /**
     * @return string[]
     */
    public function readDirectory(string $path): array
    {
        return $this->internalFilesystem->readDirectory($this->getInternalPath($path));
    }

    public function remove(string $path): void
    {
        $this->internalFilesystem->remove($this->getInternalPath($path));
    }

    public function getEntry(string $path): SubFilesystemEntry
    {
        return new SubFilesystemEntry($this, $path);
    }

    public function getInternalFilesystem(): Filesystem
    {
        return $this->internalFilesystem;
    }

    public function getInternalPath(string $path): string
    {
        $internalPath = Path::makeAbsolute($path, $this->root);

        if (!$internalPath || !Path::isBasePath($this->root, $internalPath)) {
            if ($path !== '') {
                throw new FileNotFoundException(null, 0, null, $path);
            }

            $internalPath = $this->root;
        }

        return $internalPath;
    }

    public function getPath(string $internalPath): string
    {
        $path = str_replace([$this->root, DIRECTORY_SEPARATOR], ['', '/'], $internalPath);

        return ltrim($path, '/');
    }
}
