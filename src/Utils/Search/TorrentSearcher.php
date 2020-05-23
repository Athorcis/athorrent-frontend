<?php

namespace Athorrent\Utils\Search;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class TorrentSearcher
{
    private function getCrawlers($source): array
    {
        $crawlers = [
            'tpb' => new ThePirateBayCrawler('tpb'),
            'nyaa' => new NyaaTorrentsCrawler('nyaa'),
            'anidex' => new AniDexCrawler('anidex')
        ];

        if ($source === 'all') {
            return $crawlers;
        }

        if (isset($crawlers[$source])) {
            return [$crawlers[$source]];
        }

        return [];
    }

    public function search($query, $source): array
    {
        $client = new Client();
        $crawlers = $this->getCrawlers($source);

        $promises = [];

        foreach ($crawlers as $crawler) {
            $promises[] = $crawler->initializeRequest($client, $query);
        }

        $results = [];

        foreach (Promise\settle($promises)->wait() as $promise) {
            if ($promise['state'] === 'fulfilled' && !empty($promise['value']['results'])) {
                $results[$promise['value']['id']] = $promise['value']['results'];
            }
        }

        return $results;
    }
}
