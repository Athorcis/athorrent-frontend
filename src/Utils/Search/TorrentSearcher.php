<?php

namespace Athorrent\Utils\Search;

use Athorrent\Utils\Search\Source\AniDexSource;
use Athorrent\Utils\Search\Source\MagnetDLSource;
use Athorrent\Utils\Search\Source\NyaaSource;
use Athorrent\Utils\Search\Source\ThePirateBaySource;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function array_flip;
use function array_intersect_key;

class TorrentSearcher
{
    private HttpClientInterface $http;

    /** @var TorrentSourceInterface[] */
    private array $sources;

    public function __construct(HttpClientInterface $http)
    {
        $this->http = $http;
        $this->sources = $this->mapSources([
            new ThePirateBaySource(),
            new NyaaSource(),
            new AniDexSource(),
            new MagnetDLSource()
        ]);
    }

    /**
     * @param TorrentSourceInterface[] $sources
     * @return TorrentSourceInterface[]
     */
    private function mapSources(array $sources): array
    {
        $keys = array_map(function ($source) {
            return $source->getId();
        }, $sources);

        return array_combine($keys, $sources);
    }

    /**
     * @param string|string[]|null $sourceIds
     * @return TorrentSourceInterface[]
     */
    public function getSources(array|string $sourceIds = null): array
    {
        if ($sourceIds === null) {
            return $this->sources;
        }

        if (is_string($sourceIds)) {
            $sourceIds = [$sourceIds];
        }

        return array_intersect_key($this->sources, array_flip($sourceIds));
    }

    /**
     * @param string $query
     * @param TorrentSourceInterface[] $sources
     * @return ResponseInterface[]
     * @throws TransportExceptionInterface
     */
    protected function sendRequests(string $query, array $sources): array
    {
        $responses = [];

        foreach ($sources as $id => $source) {
            $responses[$source->getId()] = $source->sendRequest($this->http, $query);
        }

        return $responses;
    }

    /**
     * @param string $query
     * @param string|string[]|null $sourceIds
     * @return TorrentInfo[][]
     * @throws TransportExceptionInterface
     */
    public function search(string $query, array|string $sourceIds = null): array
    {
        $sources = $this->getSources($sourceIds);
        $responses = $this->sendRequests($query, $sources);

        $results = [];

        foreach ($this->http->stream($responses) as $response => $chunk) {

            try {
                // If we don't check the status code ourselves, the stream method will throw when an error happens
                if ($chunk->isFirst()) {
                    $response->getStatusCode();
                }
                elseif ($chunk->isLast()) {
                    $sourceId = $response->getInfo('user_data')['sourceId'];
                    $torrents = $sources[$sourceId]->parseResponse($response);

                    if (count($torrents) > 0) {
                        $results[$sourceId] = $torrents;
                    }
                }
            }
            catch (HttpExceptionInterface $exception) {
                $sourceId = $response->getInfo('user_data')['sourceId'];
                $results[$sourceId] = $exception;
            }
        }

        return $results;
    }
}
