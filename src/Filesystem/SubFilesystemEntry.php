<?php

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** @property SubFilesystem $filesystem */
class SubFilesystemEntry extends AbstractFilesystemEntry
{
    use MimeTypeCheckerTrait;

    protected $internalEntry;

    public function __construct(SubFilesystem $filesystem, string $path, FilesystemEntry $internalEntry = null)
    {
        if ($internalEntry === null) {
            $internalPath = $filesystem->getInternalPath($path);
            $internalEntry = new FilesystemEntry($filesystem->getInternalFilesystem(), $internalPath);
        } else {
            $internalPath = $internalEntry->path;
        }

        parent::__construct($filesystem, $filesystem->getPath($internalPath));
        $this->internalEntry = $internalEntry;
    }

    public function isRoot(): bool
    {
        return $this->filesystem->getRoot() === $this->internalEntry->path;
    }

    public function exists(): bool
    {
        return $this->internalEntry->exists();
    }

    public function isDirectory(): bool
    {
        return $this->internalEntry->isDirectory();
    }

    public function isFile(): bool
    {
        return $this->internalEntry->isFile();
    }

    public function getModificationTimestamp(): int
    {
        return $this->internalEntry->getModificationTimestamp();
    }

    public function getSize(): int
    {
        return $this->internalEntry->getSize();
    }

    public function getMimeType(): string
    {
        return $this->internalEntry->getMimeType();
    }

    /**
     * @param bool $includeParentDirectory
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array
    {
        $entries = [];
        $internalEntries = $this->internalEntry->readDirectory($includeParentDirectory);

        foreach ($internalEntries as $internalEntry) {
            $entries[] = new static($this->filesystem, '', $internalEntry);
        }

        return $entries;
    }

    public function readFile(): string
    {
        return $this->internalEntry->readFile();
    }

    public function remove(): void
    {
        $this->internalEntry->remove();
    }

    public function toBinaryFileResponse(): BinaryFileResponse
    {
        return $this->internalEntry->toBinaryFileResponse();
    }
}
