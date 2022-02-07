<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;

class SubFilesystem extends AbstractFilesystem
{
    private Filesystem $internalFilesystem;

    public function __construct(string $root)
    {
        parent::__construct($root);
        $this->internalFilesystem = new Filesystem('/');
    }

    /**
     * @param string $path
     * @return int
     */
    public function getSize(string $path): int
    {
        return $this->internalFilesystem->getSize($this->getInternalPath($path));
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function readDirectory(string $path): array
    {
        return $this->internalFilesystem->readDirectory($this->getInternalPath($path));
    }

    /**
     * @param string $path
     */
    public function remove(string $path): void
    {
        $this->internalFilesystem->remove($this->getInternalPath($path));
    }

    public function getEntry(string $path): FilesystemEntryInterface
    {
        return new SubFilesystemEntry($this, $path);
    }

    /**
     * @return Filesystem
     */
    public function getInternalFilesystem(): Filesystem
    {
        return $this->internalFilesystem;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getInternalPath(string $path): string
    {
        $internalPath = Path::makeAbsolute($path, $this->root);

        if (!$internalPath || !Path::isBasePath($this->root, $internalPath)) {
            if (empty($path)) {
                $internalPath = $this->root;
            } else {
                throw new FileNotFoundException(null, 0, null, $path);
            }
        }

        return $internalPath;
    }

    /**
     * @param string $internalPath
     * @return string
     */
    public function getPath(string $internalPath): string
    {
        $path = str_replace([$this->root, DIRECTORY_SEPARATOR], ['', '/'], $internalPath);

        return ltrim($path, '/');
    }
}
