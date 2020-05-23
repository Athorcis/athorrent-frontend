<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class SubFilesystem extends AbstractFilesystem
{
    /** @var Filesystem */
    private $internalFilesystem;

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
        $internalPath = str_replace('/', DIRECTORY_SEPARATOR, realpath($this->root . '/' . $path));

        if (!$internalPath || strrpos($internalPath, $this->root, -strlen($internalPath)) === false) {
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
