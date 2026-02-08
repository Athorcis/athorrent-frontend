<?php

namespace Athorrent\Filesystem;


use Athorrent\Backend\BackendUnavailableException;
use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManager;
use Exception;
use Symfony\Component\Filesystem\Path;

class TorrentFilesystem extends UserFilesystem
{
    public const int MATCH_TORRENT_CONTAINS = 1;
    public const int MATCH_TORRENT_IS = 2;
    public const int MATCH_TORRENT_ANY = 3;

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
    protected function matchTorrentImplementation(string $path, int $mode): bool
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

        // We add a trailing slash to avoid partial matches
        $path = Path::canonicalize($path) . '/';

        foreach ($torrentPaths as &$torrentPath) {
            $torrentPath .= '/';
        }
        unset($torrentPath);

        foreach ($torrentPaths as $torrentPath) {
            if ($mode & self::MATCH_TORRENT_IS && str_starts_with($path, $torrentPath)) {
                return true;
            }
            elseif ($mode & self::MATCH_TORRENT_CONTAINS && str_starts_with($torrentPath, $path) && $path !== $torrentPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function matchTorrent(string $path, int $mode): bool
    {
        if (!isset($this->torrentsMap[$path . $mode])) {
            $this->torrentsMap[$path . $mode] = $this->matchTorrentImplementation($path, $mode);
        }

        return $this->torrentsMap[$path . $mode];
    }
}
