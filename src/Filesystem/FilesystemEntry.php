<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;
use Symfony\Component\Mime\MimeTypes;

/**
 * @template TFilesystem of Filesystem = Filesystem
 * @extends AbstractFilesystemEntry<TFilesystem>
 */
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
        $timestamp = filemtime($this->path);

        if ($timestamp === false) {
            throw new \RuntimeException('Unable to get file modification timestamp for ' . $this->path);
        }

        return $timestamp;
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
                $mimeUtils->registerGuesser(new FileBinaryMimeTypeGuesser());
            }

            $this->mimeType = $mimeUtils->guessMimeType($this->path);

            if ($this->mimeType === null) {
                throw new \RuntimeException('Unable to guess mime type for ' . $this->path);
            }
        }

        return $this->mimeType;
    }

    public function readFile(): string
    {
        $content = file_get_contents($this->path);

        if ($content === false) {
            throw new \RuntimeException('Unable to read file ' . $this->path);
        }

        return $content;
    }
}
