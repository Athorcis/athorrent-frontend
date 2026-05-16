<?php

namespace Athorrent\Utils;

use Athorrent\Backend\LegacyBackend;
use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\FileUtils;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class TorrentManager extends AbstractTorrentManager
{
    private LegacyBackend $backend;

    /**
     * TorrentManager constructor.
     */
    public function __construct(Filesystem $fs, User $user, LegacyBackend $backend)
    {
        parent::__construct($fs, $user);
        $this->backend = $backend;
    }

    /**
     * S'assure que le dossier d'upload des fichiers torrents existe et retourne son chemin
     */
    protected function ensureTorrentsDirExists(): string
    {
        $torrentsDir = $this->getTorrentsDirectory();
        $this->fs->mkdir($torrentsDir);

        return $torrentsDir;
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

    public function storeUploadedTorrentFile(UploadedFile $file): void
    {
        $torrentsDir = $this->ensureTorrentsDirExists();
        $file->move($torrentsDir, $file->getClientOriginalName());
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

        $result = $this->backend->call('addTorrentFromFile', [
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
        return $this->backend->call('addTorrentFromMagnet', ['magnet' => $magnet]);
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
        return $this->backend->call('getTorrents');
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getPaths(): array
    {
        $paths = $this->backend->call('getPaths');

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
        return $this->backend->call('pauseTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function resumeTorrent(string $hash): string
    {
        return $this->backend->call('resumeTorrent', ['hash' => $hash]);
    }

    /**
     * @throws Exception
     */
    public function removeTorrent(string $hash): string
    {
        return $this->backend->call('removeTorrent', ['hash' => $hash]);
    }

    /**
     * @return array<array{id: string, url: string, peers: int, message: string}>
     * @throws Exception
     */
    public function listTrackers(string $hash): array
    {
        return $this->backend->call('listTrackers', ['hash' => $hash]);
    }
}
