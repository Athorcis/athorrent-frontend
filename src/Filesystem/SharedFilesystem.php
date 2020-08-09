<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManager;

class SharedFilesystem extends TorrentFilesystem
{
    public function __construct(TorrentManager $torrentManager, User $accessor, Sharing $sharing)
    {
        parent::__construct($torrentManager, $accessor, $sharing->getPath());
    }

    public function getEntry(string $path): FilesystemEntryInterface
    {
        return new SharedFilesystemEntry($this, $path);
    }

    public function isWritable(): bool
    {
        return false;
    }
}
