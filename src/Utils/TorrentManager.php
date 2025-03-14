<?php

namespace Athorrent\Utils;

use Athorrent\Backend\Backend;
use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\FileUtils;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

readonly class TorrentManager
{
    private Backend $service;

    /**
     * TorrentManager constructor.
     */
    public function __construct(private Filesystem $fs, private User $user)
    {
        $this->service = new Backend($user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTorrentsDirectory(): string
    {
        return $this->user->getNewTorrentsPath();
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

        $result = $this->service->callGuarded('addTorrentFromFile', [
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
        return $this->service->callGuarded('addTorrentFromMagnet', ['magnet' => $magnet]);
    }

    /**
     * @return array<array{
     *     state: string, paused: bool,
     *     total_payload_download: string, total_payload_upload: string, size: string, progress: float,
     *     download_rate: float, download_payload_rate: float, upload_rate: float, upload_payload_rate: float,
     *     num_seeds: int, num_peers: int, num_complete: int, num_incomplete: int,
     *     list_seeds: int, list_peers: int, hash: string}>
     * @throws Exception
     */
    public function getTorrents(): array
    {
        return $this->service->callGuarded('getTorrents');
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getPaths(): array
    {
        $paths = $this->service->callGuarded('getPaths');

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
        return $this->service->callGuarded('pauseTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function resumeTorrent(string $hash): string
    {
        return $this->service->callGuarded('resumeTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function removeTorrent(string $hash): string
    {
        return $this->service->callGuarded('removeTorrent', ['hash' => $hash]);
    }

    /**
     * @return array<array{id: string, url: string, peers: int, message: string}>
     * @throws Exception
     */
    public function listTrackers(string $hash): array
    {
        return $this->service->callGuarded('listTrackers', ['hash' => $hash]);
    }
}
