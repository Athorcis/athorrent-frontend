<?php

namespace Athorrent\Filesystem;

class SharedFilesystemEntry extends TorrentFilesystemEntry
{
    public function __construct(SharedFilesystem $filesystem, string $path, FilesystemEntry $internalEntry = null)
    {
        parent::__construct($filesystem, $path, $internalEntry);
    }

    public function getName(): string
    {
        return basename($this->internalEntry->path);
    }

    /**
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array
    {
        if ($this->isFile()) {
            return [$this];
        }

        return parent::readDirectory($includeParentDirectory);
    }
}
