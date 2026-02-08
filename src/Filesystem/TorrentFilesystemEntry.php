<?php

namespace Athorrent\Filesystem;

use Exception;

/** @property TorrentFilesystem $filesystem */
class TorrentFilesystemEntry extends UserFilesystemEntry
{
    private ?bool $torrent = null;

    private ?bool $torrentBound = null;

    public function __construct(TorrentFilesystem $filesystem, string $path, FilesystemEntry|null $internalEntry = null)
    {
        parent::__construct($filesystem, $path, $internalEntry);
    }

    /**
     * @throws Exception
     */
    public function isTorrent(): bool
    {
        return $this->torrent ??= $this->filesystem->matchTorrent($this->internalEntry->path, TorrentFilesystem::MATCH_TORRENT_IS);
    }

    /**
     * @throws Exception
     */
    public function isTorrentBound(): bool
    {
        return $this->torrentBound ??= $this->filesystem->matchTorrent($this->internalEntry->path, TorrentFilesystem::MATCH_TORRENT_ANY);
    }

    /**
     * @throws Exception
     */
    public function isWritable(): bool
    {
        return parent::isWritable() && !$this->isTorrent();
    }

    public function isDeletable(): bool
    {
        return parent::isDeletable() && !$this->isTorrentBound();
    }

    /**
     * @throws Exception
     */
    public function isCachable(): bool
    {
        return !$this->isTorrentBound();
    }
}
