<?php

namespace Athorrent\Utils\Search;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class ThePirateBayCrawler
{
    private $id;

    private $domain;

    public function __construct($id, $domain = null)
    {
        $this->id = $id;

        if ($domain) {
            $this->domain = $domain;
        } else {
            $this->domain = 'thepiratebay.org';
        }
    }

    private function replaceNonBreakingSpace($string)
    {
        return str_replace("\xc2\xa0", ' ', $string);
    }

    private function parseDate($date)
    {
        $date = $this->replaceNonBreakingSpace($date);

        if (preg_match('/^(\d{2}-\d{2}) (\d{2}:?\d{2})$/', $date, $matches)) {
            if (strlen($matches[2]) === 5) {
                $date = date('Y') . '-' . $date;
            } else {
                $date = $matches[2] . '-' . $matches[1];
            }
        }

        return strtotime(str_replace('Y-day', 'Yesterday', $date . ' GMT'));
    }

    private function getAge($date)
    {
        return time() - $this->parseDate($date);
    }

    public function initializeRequest(Client $client, $query)
    {
        $cookieJar = CookieJar::fromArray([
            'lw' => 's'
        ], '.' . $this->domain);

        $promise = $client->getAsync('https://' . $this->domain . '/search/' . urlencode($query), ['cookies' => $cookieJar]);

        return $promise->then(function (ResponseInterface $response) {
            return ['id' => $this->id, 'results' => $this->parseResponse($response)];
        });
    }

    public function parseResponse(ResponseInterface $response)
    {
        $crawler = new Crawler(strval($response->getBody()));
        $rows = $crawler->filter('#searchResult > tr');

        $torrents = [];

        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');

            $torrents[] = [
                'name' => str_replace('Details for ', '' ,$cells->eq(1)->filter('a')->attr('title')),
                'href' => 'https://' . $this->domain . $cells->eq(1)->filter('a')->attr('href'),
                'age' => $this->getAge($cells->eq(2)->text()),
                'magnet' => $cells->eq(3)->filter('a')->eq(0)->attr('href'),
                'size' => $cells->eq(4)->text(),
                'seeders' => intval($cells->eq(5)->text()),
                'leechers' => intval($cells->eq(6)->text())
            ];
        }

        return $torrents;
    }
}
