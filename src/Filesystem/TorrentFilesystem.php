<?php

namespace Athorrent\Filesystem;


use Athorrent\Backend\BackendUnavailableException;
use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManager;
use Exception;
use Symfony\Component\Filesystem\Path;

class TorrentFilesystem extends UserFilesystem
{
    protected TorrentManager $torrentManager;

    protected bool $torrentManagerFailed = false;

    /** @var string[] */
    protected ?array $torrentPaths = null;

    /** @var bool[] */
    protected ?array $torrentsMap = null;

    public function __construct(TorrentManager $torrentManager, ?User $accessor, string $path = '')
    {
        parent::__construct($torrentManager->getUser(), $accessor, $path);
        $this->torrentManager = $torrentManager;
    }

    public function getEntry(string $path): TorrentFilesystemEntry
    {
        return new TorrentFilesystemEntry($this, $path);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    protected function getTorrentPaths(): array
    {
       return $this->torrentPaths ??= $this->torrentManager->getPaths();
    }

    /**
     * @throws Exception
     */
    protected function isTorrentImplementation(string $path): bool
    {
        if ($this->torrentManagerFailed) {
            return true;
        }

        try {
            $torrentPaths = $this->getTorrentPaths();
        }
        catch (BackendUnavailableException) {
            $this->torrentManagerFailed = true;
            return true;
        }

        $path = Path::canonicalize($path);
        $index = -strlen($path);

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($path, $torrentPath, $index) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
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
