<?php

namespace Athorrent\Filesystem;

class Filesystem extends AbstractFilesystem
{
    /** @var FileUtils */
    private $fileUtils;

    public function __construct(string $root)
    {
        parent::__construct($root);
        $this->fileUtils = new FileUtils();
    }

    public function getSize(string $path): int
    {
        return $this->fileUtils->getSize($path);
    }

    public function readDirectory(string $path): array
    {
        $iterator = new \FilesystemIterator($path, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
        return iterator_to_array($iterator);
    }

    public function remove(string $path): void
    {
        $this->fileUtils->remove($path);
    }

    public function getEntry(string $path): FilesystemEntryInterface
    {
        return new FilesystemEntry($this, $path);
    }
}
