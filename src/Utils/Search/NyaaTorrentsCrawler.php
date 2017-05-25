<?php

namespace Athorrent\Utils\Search;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class NyaaTorrentsCrawler
{
    private $id;

    private $domain;

    public function __construct($id, $domain = null)
    {
        $this->id = $id;

        if ($domain) {
            $this->domain = $domain;
        } else {
            $this->domain = 'nyaa.si';
        }
    }

    private function getAge($date)
    {
        return time() - strtotime($date);
    }

    public function initializeRequest(Client $client, $query)
    {
        $promise = $client->getAsync('https://' . $this->domain . '/?q=' . urlencode($query));

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
                'name' => trim($cells->eq(1)->text()),
                'href' => 'https://' . $this->domain . $cells->eq(1)->filter('a')->attr('href'),
                'age' => $this->getAge($cells->eq(4)->text()),
                'magnet' => $cells->eq(2)->filter('a')->eq(1)->attr('href'),
                'size' => $cells->eq(3)->text(),
                'seeders' => intval($cells->eq(5)->text()),
                'leechers' => intval($cells->eq(6)->text())
            ];
        }

        return $torrents;
    }
}
