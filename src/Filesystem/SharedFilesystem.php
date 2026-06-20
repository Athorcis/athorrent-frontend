<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManagerInterface;

class SharedFilesystem extends TorrentFilesystem
{
    public function __construct(TorrentManagerInterface $torrentManager, ?User $accessor, Sharing $sharing)
    {
        parent::__construct($torrentManager, $accessor, $sharing->getPath());
    }

    public function getEntry(string $path): SharedFilesystemEntry
    {
        return new SharedFilesystemEntry($this, $path);
    }

    public function isWritable(): bool
    {
        return false;
    }
}
