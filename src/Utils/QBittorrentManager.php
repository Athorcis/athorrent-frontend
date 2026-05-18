<?php

namespace Athorrent\Utils;

use Athorrent\Backend\QBittorrentBackend;
use Athorrent\Database\Entity\User;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

readonly class QBittorrentManager extends AbstractTorrentManager
{
    private QBittorrentBackend $backend;

    public function __construct(
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
    protected function request(string $method, string $path, array $options = [])
    {
        $response = $this->backend->request($method, $path, $options);

        $body = trim($response->getContent());

        if ($body === '') {
            return null;
        }

        return json_decode($body, true);
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromUrl(string $url): array
    {
        $beforeHashes = $this->getCurrentHashes();
        $this->request('POST', '/api/v2/torrents/add', [
            'body' => ['urls' => $url],
        ]);

        return $this->resolveAddedHash($beforeHashes);
    }

    public function storeUploadedTorrentFile(UploadedFile $file): void
    {
        $torrentsDir = $this->ensureTorrentsDirExists();
        $file->move($torrentsDir, $file->getClientOriginalName());
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromFile(string $path): array
    {
        $beforeHashes = $this->getCurrentHashes();
        $absolutePath = Path::canonicalize($path);
        $torrentFile = fopen($absolutePath, 'rb');

        if ($torrentFile === false) {
            throw new Exception('Unable to open torrent file: ' . $absolutePath);
        }

        try {
            $this->request('POST', '/api/v2/torrents/add', [
                'body' => [
                    'torrents' => $torrentFile,
                ],
            ]);
        } finally {
            if (is_resource($torrentFile)) {
                fclose($torrentFile);
            }
        }

        return $this->resolveAddedHash($beforeHashes);
    }

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromMagnet(string $magnet): array
    {
        $beforeHashes = $this->getCurrentHashes();
        $this->request('POST', '/api/v2/torrents/add', [
            'body' => ['urls' => $magnet],
        ]);

        return $this->resolveAddedHash($beforeHashes);
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

        foreach ($torrents as $torrent) {
            $contentPath = (string) ($torrent['content_path'] ?? '');

            if ($contentPath !== '') {
                $paths[] = Path::canonicalize($contentPath);
            }
        }

        return array_values(array_unique($paths));
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

    private function getCurrentHashes(): array
    {
        $torrents = $this->request('GET', '/api/v2/torrents/info');
        $hashes = [];

        foreach ($torrents as $torrent) {
            $hash = (string) ($torrent['hash'] ?? '');

            if ($hash !== '') {
                $hashes[] = $hash;
            }
        }

        return $hashes;
    }

    #[ArrayShape(['hash' => 'string'])]
    private function resolveAddedHash(array $beforeHashes): array
    {
        $afterHashes = $this->getCurrentHashes();
        $newHashes = array_values(array_diff($afterHashes, $beforeHashes));

        if ($newHashes === []) {
            return ['hash' => ''];
        }

        return ['hash' => $newHashes[0]];
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
/**
 * unknown: -1,
 * moving: 16,
 * missingFiles: 17,
 * error: 18
 */
