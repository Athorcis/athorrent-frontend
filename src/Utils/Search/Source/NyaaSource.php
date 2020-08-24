<?php

namespace Athorrent\Utils\Search\Source;

use Athorrent\Utils\Search\AbstractTorrentSource;
use Athorrent\Utils\Search\TorrentInfo;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NyaaSource extends AbstractTorrentSource
{
    public function __construct(string $origin = 'https://nyaa.si')
    {
        parent::__construct('nyaa', 'Nyaa', $origin);
    }

    public function sendRequest(HttpClientInterface $http, string $query): ResponseInterface
    {
        return $this->doRequest($http, 'GET', '/?q=' . urlencode($query));
    }

    protected function parseRow(Crawler $cells): TorrentInfo
    {
        return $this->createTorrentInfo(
            $cells->eq(1)->text(),
            $cells->eq(1)->filter('a')->attr('href'),
            $cells->eq(4)->text(),
            $cells->eq(2)->filter('a')->eq(1)->attr('href'),
            $cells->eq(3)->text(),
            $cells->eq(5)->text(),
            $cells->eq(6)->text()
        );
    }

    protected function parseAge(string $date): int
    {
        return time() - strtotime($date);
    }
}
