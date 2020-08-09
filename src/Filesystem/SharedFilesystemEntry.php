<?php

namespace Athorrent\Filesystem;

class SharedFilesystemEntry extends TorrentFilesystemEntry
{
    public function __construct(SharedFilesystem $filesystem, string $path, FilesystemEntry $intenalEntry = null)
    {
        parent::__construct($filesystem, $path, $intenalEntry);
    }

    public function getName(): string
    {
        return basename($this->internalEntry->path);
    }

    public function readDirectory(bool $includeParentDirectory = false): array
    {
        if ($this->isFile()) {
            return [$this];
        }

        return parent::readDirectory($includeParentDirectory);
    }
}
