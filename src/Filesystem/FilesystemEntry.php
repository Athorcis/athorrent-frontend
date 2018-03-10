<?php

namespace Athorrent\Filesystem;

class FilesystemEntry extends AbstractFilesystemEntry
{
    public function __construct(Filesystem $filesystem, string $path)
    {
        parent::__construct($filesystem, $path);
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    public function isFile(): bool
    {
        return is_file($this->path);
    }

    public function getModificationTimestamp(): int
    {
        return filemtime($this->path);
    }

    public function getSize(): int
    {
        return $this->filesystem->getSize($this->path);
    }

    public function getMimeType(): string
    {
        $finfo = new \finfo(FILEINFO_MIME);
        return $finfo->file($this->path);
    }

    public function readFile(): string
    {
        return file_get_contents($this->path);
    }
}
