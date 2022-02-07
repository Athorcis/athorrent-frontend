<?php

namespace Athorrent\Filesystem;

use Exception;

/** @property TorrentFilesystem $filesystem */
class TorrentFilesystemEntry extends UserFilesystemEntry
{
    private ?bool $torrent = null;

    public function __construct(TorrentFilesystem $filesystem, string $path, FilesystemEntry $internalEntry = null)
    {
        parent::__construct($filesystem, $path, $internalEntry);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isTorrent(): bool
    {
        if ($this->torrent === null) {
            $this->torrent = $this->filesystem->isTorrent($this->internalEntry->path);
        }

        return $this->torrent;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isWritable(): bool
    {
        return parent::isWritable() && !$this->isTorrent();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isCachable(): bool
    {
        return !$this->isTorrent();
    }
}
