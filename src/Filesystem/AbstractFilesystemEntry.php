<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class AbstractFilesystemEntry implements FilesystemEntryInterface
{
    public function __construct(protected AbstractFilesystem $filesystem, protected string $path)
    {
    }

    /**
     * @return AbstractFilesystem
     */
    public function getFilesystem(): AbstractFilesystem
    {
        return $this->filesystem;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRealPath(): string
    {
        return $this->path;
    }

    public function isRoot(): bool
    {
        return $this->filesystem->getRoot() === $this->path;
    }

    public function isFilesystemWritable(): bool
    {
        return $this->filesystem->isWritable();
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    /**
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array
    {
        $entries = [];

        if ($includeParentDirectory) {
            $entries[] = new static($this->filesystem, Path::canonicalize($this->path . '/..'));
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
