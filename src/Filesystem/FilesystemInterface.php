<?php

namespace Athorrent\Filesystem;

interface FilesystemInterface
{
    public function getRoot(): string;

    public function getSize(string $path): int;

    /**
     * @return string[]
     */
    public function readDirectory(string $path): array;

    public function remove(string $path): void;

    public function getEntry(string $path): FilesystemEntryInterface;
}
