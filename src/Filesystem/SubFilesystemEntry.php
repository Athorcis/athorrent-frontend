<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @template TFilesystem of SubFilesystem = SubFilesystem
 * @extends AbstractFilesystemEntry<TFilesystem>
 */
class SubFilesystemEntry extends AbstractFilesystemEntry
{
    use MimeTypeCheckerTrait;

    protected FilesystemEntry $internalEntry;

    public function __construct(SubFilesystem $filesystem, string $path, FilesystemEntry|null $internalEntry = null)
    {
        if ($internalEntry instanceof FilesystemEntry) {
            $internalPath = $internalEntry->path;
        }
        else {
            $internalPath = $filesystem->getInternalPath($path);
            $internalEntry = new FilesystemEntry($filesystem->getInternalFilesystem(), $internalPath);
        }

        parent::__construct($filesystem, $filesystem->getPath($internalPath));
        $this->internalEntry = $internalEntry;
    }

    public function getRealPath(): string
    {
        return $this->internalEntry->getRealPath();
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
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array
    {
        $entries = [];
        $internalEntries = $this->internalEntry->readDirectory($includeParentDirectory);

        foreach ($internalEntries as $internalEntry) {
            // @phpstan-ignore new.static
            $entries[] = new static($this->filesystem, '', $internalEntry);
        }

        return $entries;
    }

    public function readFile(?int $maxBytes = null): string
    {
        return $this->internalEntry->readFile($maxBytes);
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
