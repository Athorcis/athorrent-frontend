<?php

namespace Athorrent\Utils\Search\Source;

use Athorrent\Utils\Search\AbstractTorrentSource;
use Athorrent\Utils\Search\TorrentInfo;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function urlencode;

class AniDexSource extends AbstractTorrentSource
{
    public function __construct(string $origin = 'https://anidex.info')
    {
        parent::__construct('anidex', 'AniDex', $origin);
    }

    public function sendRequest(HttpClientInterface $http, string $query): ResponseInterface
    {
        return $this->doRequest($http, 'GET', '/?q=' . urlencode($query), [
            'headers' => [
                'Cookie' => '__ddg2=0; __ddg1=0;'
        ]]);
    }

    protected function parseRow(Crawler $cells): TorrentInfo
    {
        return $this->createTorrentInfo(
            $cells->eq(2)->filter('.span-1440')->text(),
            $cells->eq(2)->filter('a')->attr('href'),
            $cells->eq(7)->text(),
            $cells->eq(5)->filter('a')->attr('href'),
            $cells->eq(6)->text(),
            $cells->eq(8)->text(),
            $cells->eq(9)->text()
        );
    }

    protected function parseAge(string $age): int
    {
        static $magnitudes = [
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
            'mo' => 2592000,
            'y' => 31557600
        ];

        preg_match('/^(\d{1,2})([smhdy]|mo)$/', $age, $matches);

        return $matches[1] * $magnitudes[$matches[2]];
    }
}
