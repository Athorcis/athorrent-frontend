<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Mime\MimeTypes;

class FilesystemEntry extends AbstractFilesystemEntry
{
    protected ?bool $isDir = null;

    protected ?bool $isFile = null;

    protected ?string $mimeType = null;

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
       return $this->isDir ??= is_dir($this->path);
    }

    public function isFile(): bool
    {
        return $this->isFile ??= is_file($this->path);
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
            static $mimeUtils;

            if ($mimeUtils === null) {
                $mimeUtils = new MimeTypes();
            }

            $this->mimeType = $mimeUtils->guessMimeType($this->path);
        }

        return $this->mimeType;
    }

    public function readFile(): string
    {
        return file_get_contents($this->path);
    }
}
