<?php

namespace Athorrent\Filesystem;

class FilesystemEntry extends AbstractFilesystemEntry
{
    protected $isDir;

    protected $isFile;

    protected $mimeType;

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
        if ($this->isDir === null) {
            $this->isDir = is_dir($this->path);
        }

        return $this->isDir;
    }

    public function isFile(): bool
    {
        if ($this->isFile === null) {
            $this->isFile = is_file($this->path);
        }

        return $this->isFile;
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
        if ($this->mimeType === null) {
            $finfo = new \finfo(FILEINFO_MIME);
            $this->mimeType = $finfo->file($this->path);
        }

        return $this->mimeType;
    }

    public function readFile(): string
    {
        return file_get_contents($this->path);
    }
}
