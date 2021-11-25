<?php

namespace Athorrent\Filesystem;

use Exception;

/**
 * @property TorrentFilesystem $filesystem
 */
class TorrentFilesystemEntry extends UserFilesystemEntry
{
    /** @var bool */
    private $torrent;

    public function __construct(TorrentFilesystem $filesystem, string $path, FilesystemEntry $internalEnty = null)
    {
        parent::__construct($filesystem, $path, $internalEnty);
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
