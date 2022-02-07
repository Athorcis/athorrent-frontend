<?php

namespace Athorrent\Utils\Search\Source;

use Athorrent\Utils\Search\AbstractTorrentSource;
use Athorrent\Utils\Search\TorrentInfo;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ThePirateBaySource extends AbstractTorrentSource
{
    public function __construct(string $origin = 'https://thepiratebay0.org')
    {
        parent::__construct('tbp', 'The Pirate Bay', $origin, '#searchResult > tr');
    }

    public function sendRequest(HttpClientInterface $http, string $query): ResponseInterface
    {
        return $this->doRequest($http, 'GET', '/search/' . rawurlencode($query));
    }

    protected function parseRow(Crawler $cells): ?TorrentInfo
    {
        if ($cells->count() === 4) {
            $details = $cells->eq(1)->children('.detDesc')->text();
            preg_match('/^Uploaded ([^,]+), Size ([^,]+),.+$/', $details, $matches);

            return $this->createTorrentInfo(
                preg_replace('/^Details for /', '', $cells->eq(1)->filter('.detLink')->attr('title')),
                $cells->eq(1)->filter('.detLink')->attr('href'),
                $this->replaceNonBreakingSpace($matches[1]),
                $cells->eq(1)->children('a')->attr('href'),
                $this->replaceNonBreakingSpace($matches[2]),
                $cells->eq(2)->text(),
                $cells->eq(3)->text()
            );
        }

        return null;
    }

    private function replaceNonBreakingSpace(string $string): string
    {
        return str_replace("\xc2\xa0", ' ', $string);
    }

    protected function parseAge(string $date): int
    {
        if (preg_match('/^(\d{2}-\d{2}) (\d{2}:?\d{2})$/', $date, $matches)) {
            if (strlen($matches[2]) === 5) {
                $date = date('Y') . '-' . $date;
            } else {
                $date = $matches[2] . '-' . $matches[1];
            }
        }

        $timestamp = strtotime(str_replace('Y-day', 'Yesterday', $date . ' GMT'));

        return time() - $timestamp;
    }
}
