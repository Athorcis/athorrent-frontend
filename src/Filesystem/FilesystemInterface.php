<?php

namespace Athorrent\Filesystem;

interface FilesystemInterface
{
    /**
     * @return string
     */
    public function getRoot(): string;

    /**
     * @param string $path
     * @return int
     */
    public function getSize(string $path): int;

    /**
     * @param string $path
     * @return string[]
     */
    public function readDirectory(string $path): array;

    /**
     * @param string $path
     */
    public function remove(string $path): void;

    /**
     * @param string $path
     * @return FilesystemEntryInterface
     */
    public function getEntry(string $path): FilesystemEntryInterface;
}
