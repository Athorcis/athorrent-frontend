<?php

namespace Athorrent\Filesystem;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface FilesystemEntryInterface
{
    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return bool
     */
    public function isRoot(): bool;

    /**
     * @return bool
     */
    public function exists(): bool;

    /**
     * @return bool
     * */
    public function isDirectory(): bool;

    /**
     * @return bool
     */
    public function isFile(): bool;

    /**
     * @return int
     */
    public function getModificationTimestamp(): int;

    /**
     * @return int
     */
    public function getSize(): int;

    /**
     * @return string
     */
    public function getMimeType(): string;

    /**
     * @param bool $includeParentDirectory
     * @return static[]
     */
    public function readDirectory(bool $includeParentDirectory = false): array;

    /**
     * @return string
     */
    public function readFile(): string;

    /**
     * @return void
     */
    public function remove(): void;

    /**
     * @return BinaryFileResponse
     */
    public function toBinaryFileResponse(): BinaryFileResponse;
}
