<?php

namespace Athorrent\Filesystem;


use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManager;
use Exception;

class TorrentFilesystem extends UserFilesystem
{
    /** @var TorrentManager */
    protected $torrentManager;

    /** @var string[] */
    protected $torrentPaths;

    /** @var bool[] */
    protected $torrentsMap;

    public function __construct(TorrentManager $torrentManager, ?User $accessor, string $path = '')
    {
        parent::__construct($torrentManager->getUser(), $accessor, $path);
        $this->torrentManager = $torrentManager;
    }

    public function getEntry(string $path): FilesystemEntryInterface
    {
        return new TorrentFilesystemEntry($this, $path);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    protected function getTorrentPaths(): array
    {
        if ($this->torrentPaths === null) {
            $this->torrentPaths = $this->torrentManager->getPaths();
        }

        return $this->torrentPaths;
    }

    /**
     * @param string $path
     * @return bool
     * @throws Exception
     */
    protected function isTorrentImplementation(string $path): bool
    {
        $torrentPaths = $this->getTorrentPaths();
        $index = -strlen($path);

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($path, $torrentPath, $index) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     * @throws Exception
     */
    public function isTorrent(string $path): bool
    {
        if (!isset($this->torrentsMap[$path])) {
            $this->torrentsMap[$path] = $this->isTorrentImplementation($path);
        }

        return $this->torrentsMap[$path];
    }
}
