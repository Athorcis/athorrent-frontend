<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface TorrentManagerInterface
{
    public function getUser(): User;

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromUrl(string $url): array;

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromFile(string $path): array;

    #[ArrayShape(['hash' => 'string'])]
    public function addTorrentFromMagnet(string $magnet): array;

    public function getTorrents(): array;

    public function getPaths(): array;

    public function pauseTorrent(string $hash): string;
    public function resumeTorrent(string $hash): string;

    public function removeTorrent(string $hash): string;

    public function setDownloadLimit(int $limit): void;
}
