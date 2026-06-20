<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Athorrent\Backend\QBittorrentBackend;
use Athorrent\Database\Entity\User;
use Athorrent\UserVisibleException;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

readonly class QBittorrentManager extends AbstractTorrentManager
{
    private QBittorrentBackend $backend;

    public function __construct(
        private CacheInterface $cache,
        Filesystem $fs,
        User $user,
        QBittorrentBackend $backend,
    ) {
        parent::__construct($fs, $user);
        $this->backend = $backend;
    }

    /**
     * @throws ExceptionInterface
     */
    protected function request(string $method, string $path, array $options = [], $json = true)
    {
        $response = $this->backend->request($method, $path, $options);

        $body = trim($response->getContent());

        if ($body === '') {
            return null;
        }

        if ($json) {
            return json_decode($body, true);
        }

        return $body;
    }

    /**
     * @return string[]
     * @throws ExceptionInterface
     * @throws TorrentAlreadyAdded
     */
    protected function addTorrents(array $body, string $torrent): array
    {
        try {
            $data = $this->request('POST', '/api/v2/torrents/add', [
                'body' => $body,
            ]);

            return $data['added_torrent_ids'];
        } catch (ClientExceptionInterface $e) {
            if ($e->getCode() === 409) {
                throw new TorrentAlreadyAdded($torrent, $e);
            }

            throw $e;
        }
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromUrl(string $url): array
    {
        $ids = $this->addTorrents(['urls' => $url], $url);

        if (count($ids) > 0 ){
            return ['hash' => $ids[0]];
        }

        return [];
    }

    public function storeUploadedTorrentFile(UploadedFile $file): void
    {
        $torrentsDir = $this->ensureTorrentsDirExists();
        $file->move($torrentsDir, $file->getClientOriginalName());
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromFile(string $path): array
    {
        $absolutePath = Path::canonicalize($path);
        $torrentFile = fopen($absolutePath, 'rb');

        if ($torrentFile === false) {
            throw new Exception('Unable to open torrent file: ' . $absolutePath);
        }

        try {
            $ids = $this->addTorrents(['torrents' => $torrentFile,], basename($path));

            if (count($ids) > 0 ){
                return ['hash' => $ids[0]];
            }

            return [];
        }
        finally {
            if (is_resource($torrentFile)) {
                fclose($torrentFile);
                unlink($absolutePath);
            }
        }
    }

    protected function parseMagnet(string $uri): ?array
    {
        $uriParts = parse_url($uri);

        if (!(count($uriParts) === 2  && isset($uriParts['scheme']) && $uriParts['scheme'] === 'magnet' && isset($uriParts['query']))) {
            return null;
        }

        parse_str($uriParts['query'], $params);

        if (!(isset($params['xt']) && preg_match('/urn:btih:([0-9a-fA-F]{32}|[0-9a-fA-F]{40})/', $params['xt']))) {
            return null;
        }

        return $params;
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromMagnet(string $magnet): array
    {
        if ($this->parseMagnet($magnet) === null) {
            throw new UserVisibleException('error.invalidMagnetUri');
        }

        return $this->addTorrentFromUrl($magnet);
    }

    public function getTorrents(): array
    {
        $torrents = $this->request('GET', '/api/v2/torrents/info');
        $normalizedTorrents = [];

        foreach ($torrents as $torrent) {
            $qbitState = (string) ($torrent['state'] ?? '');

            $normalizedTorrents[] = [
                'name' => (string) ($torrent['name'] ?? ''),
                'state' => $this->normalizeState($qbitState),
                'paused' => $this->isPausedState($qbitState),
                'total_payload_download' => (int) ($torrent['downloaded'] ?? 0),
                'total_payload_upload' => (int) ($torrent['uploaded'] ?? 0),
                'size' => (int) ($torrent['size'] ?? 0),
                'progress' => (float) ($torrent['progress'] ?? 0),
                'download_rate' => (float) ($torrent['dlspeed'] ?? 0),
                'download_payload_rate' => (float) ($torrent['dlspeed'] ?? 0),
                'upload_rate' => (float) ($torrent['upspeed'] ?? 0),
                'upload_payload_rate' => (float) ($torrent['upspeed'] ?? 0),
                'num_seeds' => (int) ($torrent['num_seeds'] ?? 0),
                'num_peers' => (int) ($torrent['num_leechs'] ?? 0),
                'num_complete' => (int) ($torrent['num_complete'] ?? 0),
                'num_incomplete' => (int) ($torrent['num_incomplete'] ?? 0),
                'list_seeds' => (int) ($torrent['num_complete'] ?? 0),
                'list_peers' => (int) ($torrent['num_incomplete'] ?? 0),
                'hash' => (string) ($torrent['hash'] ?? ''),
            ];
        }

        return $normalizedTorrents;
    }

    public function getPaths(): array
    {
        $torrents = $this->request('GET', '/api/v2/torrents/info');
        $paths = [];

        $qbRoot = $this->getDownloadPath();
        $filesDir = $this->user->getFilesPath();

        foreach ($torrents as $torrent) {
            $contentPath = (string) ($torrent['content_path'] ?? '');

            if ($contentPath !== '') {
                try {
                    $relativePath = $this->fs->makePathRelative($contentPath, $qbRoot);
                }
                catch (InvalidArgumentException $e) {
                    if (str_starts_with($contentPath, $qbRoot)) {
                        $relativePath = substr($contentPath, strlen($qbRoot) + 1);
                    }
                    else {
                        throw $e;
                    }
                }

                $paths[] = Path::canonicalize(Path::join($filesDir, $relativePath));
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Récupère le chemin du dossier de téléchargement par défaut
     * configuré dans qBittorrent.
     *
     * @throws ExceptionInterface
     */
    public function getDownloadPath(): string
    {
        return $this->cache->get('qb_save_path_' . $this->user->getId(), function () {
            $path = $this->request('GET', '/api/v2/app/defaultSavePath', [], false);

            if ($path === '') {
                return '';
            }

            return Path::canonicalize($path);
        });
    }

    public function pauseTorrent(string $hash): string
    {
        $this->request('POST', '/api/v2/torrents/stop', [
            'body' => ['hashes' => $hash],
        ]);

        return 'ok';
    }

    public function resumeTorrent(string $hash): string
    {
        $this->request('POST', '/api/v2/torrents/start', [
            'body' => ['hashes' => $hash],
        ]);

        return 'ok';
    }

    public function removeTorrent(string $hash): string
    {
        $this->request('POST', '/api/v2/torrents/delete', [
            'body' => [
                'hashes' => $hash,
                'deleteFiles' => 'false',
            ],
        ]);

        return 'ok';
    }

    public function listTrackers(string $hash): array
    {
        $trackers = $this->request('GET', '/api/v2/torrents/trackers', [
            'query' => ['hash' => $hash],
        ]);
        $normalizedTrackers = [];

        foreach ($trackers as $tracker) {
            $url = (string) ($tracker['url'] ?? '');

            if ($url === '' || str_starts_with($url, '**')) {
                continue;
            }

            $normalizedTrackers[] = [
                'id' => md5($url),
                'url' => $url,
                'state' => (string) ($tracker['status'] ?? ''),
                'peers' => (int) ($tracker['num_peers'] ?? -1),
                'seeds' => (int) ($tracker['num_seeds'] ?? -1),
                'message' => (string) ($tracker['msg'] ?? ''),
            ];
        }

        return $normalizedTrackers;
    }

    protected function ensureTorrentsDirExists(): string
    {
        $torrentsDir = $this->getTorrentsDirectory();
        $this->fs->mkdir($torrentsDir);

        return $torrentsDir;
    }

    private function normalizeState(string $qbitState): string
    {
        $pausedStates = ['stoppedDL', 'stoppedUP'];
        $seedingStates = ['uploading', 'forcedUP', 'stalledUP', 'queuedUP'];
        $downloadingStates = [
            'downloading',
            'forcedDL',
            'stalledDL',
            'queuedDL',
        ];

        if (in_array($qbitState, $pausedStates, true)) {
            return 'paused';
        }

        if (in_array($qbitState, $seedingStates, true)) {
            return 'seeding';
        }

        if (in_array($qbitState, $downloadingStates, true)) {
            return 'downloading';
        }

        if ($qbitState === 'disabled') {
            return 'disabled';
        }

        if ($qbitState === 'checkingUP' || $qbitState === 'checkingDL') {
            return 'checking_files';
        }

        if ($qbitState === 'checkingResumeData') {
            return 'checking_resume_data';
        }

        if ($qbitState === 'metaDL' || $qbitState === 'forcedMetaDL') {
            return 'downloading_metadata';
        }

        if ($qbitState === 'moving') {
            return 'moving';
        }

        if ($qbitState === 'missingFiles') {
            return 'missing_files';
        }

        if ($qbitState === 'error') {
            return 'error';
        }

        return 'unknown';
    }

    private function isPausedState(string $qbitState): bool
    {
        return in_array($qbitState, ['stoppedDL', 'stoppedUP'], true);
    }
}
