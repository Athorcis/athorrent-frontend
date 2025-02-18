<?php

namespace Athorrent\Filesystem;

use Exception;

/** @property TorrentFilesystem $filesystem */
class TorrentFilesystemEntry extends UserFilesystemEntry
{
    private ?bool $torrent = null;

    public function __construct(TorrentFilesystem $filesystem, string $path, FilesystemEntry|null $internalEntry = null)
    {
        parent::__construct($filesystem, $path, $internalEntry);
    }

    /**
     * @throws Exception
     */
    public function isTorrent(): bool
    {
        return $this->torrent ??= $this->filesystem->isTorrent($this->internalEntry->path);
    }

    /**
     * @throws Exception
     */
    public function isWritable(): bool
    {
        return parent::isWritable() && !$this->isTorrent();
    }

    /**
     * @throws Exception
     */
    public function isCachable(): bool
    {
        return !$this->isTorrent();
    }
}
