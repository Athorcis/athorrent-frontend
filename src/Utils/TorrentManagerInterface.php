<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @phpstan-type TorrentState 'paused'|'downloading'|'seeding'|'disabled'|'checking_files'|'checking_resume_data'|'downloading_metadata'|'moving'|'missing_files'|'error'|'unknown'
 * @phpstan-type TorrentInfo array{
 *     hash: string,
 *     name: string,
 *     state: TorrentState,
 *     progress: float,
 *     size: int,
 *     dlspeed: float,
 *     eta: int,
 *     upspeed: float,
 *     ratio: float,
 *     paused: bool,
 * }
 */

interface TorrentManagerInterface
{
    public function getUser(): User;

    /** @return array{hash: string|null} */
    public function addTorrentFromUrl(string $url): array;

    /** @return array{hash: string|null} */
    public function addTorrentFromFile(string $path): array;

    /** @return array{hash: string|null} */
    public function addTorrentFromMagnet(string $magnet): array;

    /** @return list<TorrentInfo> */
    public function getTorrents(): array;

    /** @return list<string> */
    public function getPaths(): array;

    public function pauseTorrent(string $hash): string;
    public function resumeTorrent(string $hash): string;

    public function removeTorrent(string $hash): string;

    public function setDownloadLimit(int $limit): void;
}
