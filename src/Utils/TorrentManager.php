<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\FileUtils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class TorrentManager
{
    private AthorrentService $service;

    /**
     * TorrentManager constructor.
     */
    public function __construct(EntityManagerInterface $em, private Filesystem $fs, private User $user)
    {
        $this->service = new AthorrentService($em, $fs, $user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTorrentsDirectory(): string
    {
        return $this->user->getBackendPath('new-torrents');
    }

    /**
     * S'assure que le dossier d'upload des fichiers torrents existe et retourne son chemin
     */
    public function ensureTorrentsDirExists(): string
    {
        $torrentsDir = $this->getTorrentsDirectory();
        $this->fs->mkdir($torrentsDir);

        return $torrentsDir;
    }

    protected function makePathRelative(string $path): string
    {
        $backendDir = $this->user->getBackendPath();
        return str_replace($backendDir, '<workdir>', $path);
    }

    protected function makePathAbsolute(string $path): string
    {
        $backendDir = $this->user->getBackendPath();
        return str_replace('<workdir>', $backendDir, $path);
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromUrl(string $url): array
    {
        $torrentsDir = $this->ensureTorrentsDirExists();
        $path = Path::join($torrentsDir, md5($url) . '.torrent');

        file_put_contents($path, file_get_contents($url));

        return $this->addTorrentFromFile($path);
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromFile(string $path): array
    {
        $oldFile = Path::canonicalize($path);
        $newFile = FileUtils::encodeFilename($oldFile);

        rename($oldFile, $newFile);

        $result = $this->service->call('addTorrentFromFile', [
            'file' => $this->makePathRelative($newFile)
        ]);

        unlink($newFile);

        return $result;
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromMagnet(string $magnet): array
    {
        return $this->service->call('addTorrentFromMagnet', ['magnet' => $magnet]);
    }

    /**
     * @return array[]
     * @throws Exception
     */
    public function getTorrents(): array
    {
        return $this->service->call('getTorrents');
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getPaths(): array
    {
        $paths = $this->service->call('getPaths');

        foreach ($paths as $index => $path) {
            $paths[$index] = Path::canonicalize($this->makePathAbsolute($path));
        }

        return $paths;
    }

    /**
     * @throws Exception
     */
    public function pauseTorrent(string $hash): string
    {
        return $this->service->call('pauseTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function resumeTorrent(string $hash): string
    {
        return $this->service->call('resumeTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function removeTorrent(string $hash): string
    {
        return $this->service->call('removeTorrent', ['hash' => $hash]);
    }

    /**
     * @return array[]
     * @throws Exception
     */
    public function listTrackers(string $hash): array
    {
        return $this->service->call('listTrackers', ['hash' => $hash]);
    }
}
