<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FilesystemEntryInterface
{
    public function getPath(): string;

    public function getRealPath(): string;

    public function getName(): string;

    public function isRoot(): bool;

    public function exists(): bool;

    public function isDirectory(): bool;

    public function isFile(): bool;

    public function isFilesystemWritable(): bool;

    public function getModificationTimestamp(): int;

    public function getSize(): int;

    public function getMimeType(): string;

    /**
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array;

    /**
     * @param int<0, max>|null $maxBytes
     */
    public function readFile(?int $maxBytes = null): string;

    public function remove(): void;

    public function toBinaryFileResponse(): BinaryFileResponse;
}
