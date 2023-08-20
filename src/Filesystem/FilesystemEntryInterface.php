<?php

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FilesystemEntryInterface
{
    public function getPath(): string;

    public function getName(): string;

    public function isRoot(): bool;

    public function exists(): bool;

    public function isDirectory(): bool;

    public function isFile(): bool;

    public function getModificationTimestamp(): int;

    public function getSize(): int;

    public function getMimeType(): string;

    /**
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array;

    public function readFile(): string;

    public function remove(): void;

    public function toBinaryFileResponse(): BinaryFileResponse;
}
