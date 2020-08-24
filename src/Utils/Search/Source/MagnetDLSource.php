<?php

namespace Athorrent\Utils\Search\Source;

use Athorrent\Utils\Search\AbstractTorrentSource;
use Athorrent\Utils\Search\TorrentInfo;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MagnetDLSource extends AbstractTorrentSource
{
    public function __construct(string $origin = 'https://www.magnetdl.com')
    {
        parent::__construct('magnetdl', 'MagnetDL', $origin);
    }

    public function sendRequest(HttpClientInterface $http, string $query): ResponseInterface
    {
        $query = trim(str_replace([' ', '_'], '-', $query));
        $query = preg_replace('/[^a-zA-Z0-9-]/', '', $query);

        return $this->doRequest($http, 'GET', "/{$query[0]}/{$query}/", [
            'headers' => ['Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"]
        ]);
    }

    protected function parseRow(Crawler $cells): ?TorrentInfo
    {
        if ($cells->count() === 8) {
            return $this->createTorrentInfo(
                $cells->eq(1)->filter('a')->attr('title'),
                $cells->eq(1)->filter('a')->attr('href'),
                $cells->eq(2)->text(),
                $cells->eq(0)->children('a')->attr('href'),
                $cells->eq(5)->text(),
                $cells->eq(6)->text(),
                $cells->eq(7)->text()
            );
        }

        return null;
    }

    protected function parseAge(string $age): int
    {
        static $magnitudes = [
            'min' => 60,
            'hour' => 3600,
            'day' => 86400,
            'month' => 2592000,
            'year' => 31557600
        ];

        preg_match('/^(\d{1,2}) (?:(min|hour|day|month|year)s?)$/', $age, $matches);

        return $matches[1] * $magnitudes[$matches[2]];
    }
}
