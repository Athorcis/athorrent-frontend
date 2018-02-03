<?php

namespace Athorrent\Utils\Search;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class AniDexCrawler
{
    private $id;

    private $domain;

    public function __construct($id, $domain = null)
    {
        $this->id = $id;

        if ($domain) {
            $this->domain = $domain;
        } else {
            $this->domain = 'anidex.info';
        }
    }

    private function getAge($age)
    {
        $magnitudes = [
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

    public function initializeRequest(Client $client, $query)
    {
        $promise = $client->getAsync('https://' . $this->domain . '/?q=' . urlencode($query), [
            'headers' => ['X-Requested-With' => 'XMLHttpRequest']
        ]);

        return $promise->then(function (ResponseInterface $response) {
            return ['id' => $this->id, 'results' => $this->parseResponse($response)];
        });
    }

    public function parseResponse(ResponseInterface $response)
    {
        $crawler = new Crawler(strval($response->getBody()));
        $rows = $crawler->filter('tbody > tr');

        $torrents = [];

        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');

            $torrents[] = [
                'name' => trim($cells->eq(2)->filter('.span-1440')->text()),
                'href' => 'https://' . $this->domain . $cells->eq(2)->filter('a')->attr('href'),
                'age' => $this->getAge($cells->eq(7)->text()),
                'magnet' => $cells->eq(5)->filter('a')->attr('href'),
                'size' => preg_replace('/^([KMG])B$/', '$1iB', $cells->eq(6)->text()),
                'seeders' => intval($cells->eq(8)->text()),
                'leechers' => intval($cells->eq(9)->text())
            ];
        }

        return $torrents;
    }
}
