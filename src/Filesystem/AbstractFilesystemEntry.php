<?php

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class AbstractFilesystemEntry implements FilesystemEntryInterface
{
    /** @var AbstractFilesystem */
    protected $filesystem;

    /** @var string */
    protected $path;

    public function __construct(AbstractFilesystem $filesystem, string $path)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isRoot(): bool
    {
        return $this->filesystem->getRoot() === $this->path;
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    /**
     * @param bool $includeParentDirectory
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array
    {
        $entries = [];

        if ($includeParentDirectory) {
            $entries[] = new static($this->filesystem, $this->path . '/..');
        }

        foreach ($this->filesystem->readDirectory($this->path) as $path) {
            $entries[] = new static($this->filesystem, $path);
        }

        return $entries;
    }

    public function remove(): void
    {
        $this->filesystem->remove($this->path);
    }

    public function toBinaryFileResponse(): BinaryFileResponse
    {
        return new BinaryFileResponse($this->path);
    }

    public static function compare(FilesystemEntryInterface $a, FilesystemEntryInterface $b): int
    {
        $cmp = $a->isFile() <=> $b->isFile();

        if ($cmp === 0) {
            $cmp = $a->getName() <=> $b->getName();
        }

        return $cmp;
    }
}
