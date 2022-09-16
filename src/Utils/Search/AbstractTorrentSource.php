<?php

namespace Athorrent\Utils\Search;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function preg_match;

abstract class AbstractTorrentSource implements TorrentSourceInterface
{
    public function __construct(private string $id, private string $name, protected string $origin, private string $rowFilter = 'tbody > tr', private string $cellFilter = 'td')
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function doRequest(HttpClientInterface $http, string $method, string $route, array $options = []): ResponseInterface
    {
        $options['user_data'] = ['sourceId' => $this->id];
        return $http->request($method, $this->origin . $route, $options);
    }

    abstract protected function parseRow(Crawler $cells): ?TorrentInfo;

    /**
     * @return TorrentInfo[]
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function parseResponse(ResponseInterface $response): array
    {
        $crawler = new Crawler($response->getContent());
        $rows = $crawler->filter($this->rowFilter);

        $torrents = [];

        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter($this->cellFilter);
            $torrent = $this->parseRow($cells);

            if ($torrent !== null) {
                $torrents[] = $torrent;
            }
        }

        return $torrents;
    }

    abstract protected function parseAge(string $age): int;

    protected function parseSize(string $size): int
    {
        static $magnitude = [
            'B' => 1,
            'KB' => 1000,
            'KiB' => 1024,
            'MB' => 1000 ** 2,
            'MiB' => 1024 ** 2,
            'GB' => 1000 ** 3,
            'GiB' => 1024 ** 3,
            'TB' => 1000 ** 4,
            'TiB' => 1024 ** 4,
        ];

        preg_match('/(\d+(?:\.\d+)?)\s+((?:[KMGT]i?)?B)(?:ytes)?/', $size, $matches);

        return round($matches[1] * $magnitude[$matches[2]]);
    }

    protected function createTorrentInfo(string $name, string $path, string $age, string $magnet, string $size, string $seeders, string $leechers): TorrentInfo
    {
        if (preg_match('/^https?:\/\//', $path)) {
            $url = $path;
        }
        else {
            $url = $this->origin . $path;
        }

        return new TorrentInfo(
            trim($name),
            $url,
            $this->parseAge($age),
            $magnet,
            $this->parseSize($size),
            (int)$seeders,
            (int)$leechers
        );
    }
}
