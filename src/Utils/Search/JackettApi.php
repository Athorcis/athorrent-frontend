<?php

declare(strict_types=1);

namespace Athorrent\Utils\Search;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-type JackettPrivateApi_Indexer array{
 *     id: string,
 *     name: string,
 *     description: string,
 *     type: string,
 *     configured: bool,
 *     site_link: string,
 *     alternativesitelinks: list<string>,
 *     language: string,
 *     last_error: string,
 *     potatoenabled: bool,
 *     caps: list<array{ID: string, Name: string}>,
 * }
 *
 * @phpstan-type JackettPublicApi_Result array{
 *     FirstSeen: string,
 *     Tracker: string,
 *     TrackerId: string,
 *     TrackerType: string,
 *     CategoryDesc: string,
 *     BlackholeLink: string|null,
 *     Title: string,
 *     Guid: string,
 *     Link: string|null,
 *     Details: string,
 *     PublishDate: string,
 *     Category: list<int>,
 *     Size: int|null,
 *     Files: int|null,
 *     Grabs: int|null,
 *     Description: string,
 *     RageID: int|null,
 *     TVDBId: int|null,
 *     Imdb: int|null,
 *     TMDb: int|null,
 *     TVMazeId: int|null,
 *     TraktId: int|null,
 *     DoubanId: int|null,
 *     Genres: list<string>|null,
 *     Languages: list<string>,
 *     Subs: list<string>,
 *     Year: int|null,
 *     Author: string|null,
 *     BookTitle: string|null,
 *     Publisher: string|null,
 *     Artist: string|null,
 *     Album: string|null,
 *     Label: string|null,
 *     Track: string|null,
 *     Seeders: int,
 *     Peers: int,
 *     Poster: string|null,
 *     InfoHash: string,
 *     MagnetUri: string,
 *     MinimumRatio: float|null,
 *     MinimumSeedTime: int|null,
 *     DownloadVolumeFactor: float,
 *     UploadVolumeFactor: float,
 *     Gain: float,
 * }
 *
 * @phpstan-type JackettPublicApi_Indexer array{
 *     ID: string,
 *     Name: string,
 *     Status: int,
 *     Results: int,
 *     Error: string|null,
 *     ElapsedTime: int,
 * }
 *
 * @phpstan-type JackettPublicApi_Results array{
 *     Results: list<JackettPublicApi_Result>,
 *     Indexers: list<JackettPublicApi_Indexer>,
 * }
 */
class JackettApi
{
    private ?Cookie $authCookie = null;

    public function __construct(private readonly HttpClientInterface $http) {}

    /**
     * @param array<string, mixed> $options
     */
    protected function sendRequest(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->http->request($method, $_ENV['JACKETT_ORIGIN'] . $path, $options);
    }

    protected function login(): Cookie
    {
        $response = $this->sendRequest('POST', '/UI/Dashboard', [
            'body' => ['password' => $_ENV['JACKET_ADMIN_PASSWORD']],
            'max_redirects' => 0,
        ]);

        return Cookie::fromString($response->getHeaders(false)['set-cookie'][0]);
    }

    protected function getCookieHeader(): string
    {
        if ($this->authCookie === null) {
            $this->authCookie = $this->login();
        }

        return $this->authCookie->getName() . '=' . $this->authCookie->getValue();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function queryInternalApi(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->sendRequest($method, $path, array_merge_recursive($options, [
            'headers' => ['Cookie' => $this->getCookieHeader()],
        ]));
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function queryExternalApi(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->sendRequest($method, $path, array_merge_recursive($options, [
            'query' => ['apikey' => $_ENV['JACKET_API_KEY']],
        ]));
    }

    /**
     * @param array<string, string> $query
     * @return list<JackettPrivateApi_Indexer>
     */
    public function getIndexers(array $query = []): array
    {
        $response = $this->queryInternalApi('GET', '/api/v2.0/indexers', [
            'query' => $query,
        ]);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<JackettPrivateApi_Indexer>
     */
    public function getConfiguredIndexers(): array
    {
        return $this->getIndexers(['configured' => 'true']);
    }

    /**
     * @return list<JackettPublicApi_Results>
     */
    public function getResults(string $query, string $indexer = 'all'): array
    {
        $response = $this->queryExternalApi('GET', '/api/v2.0/indexers/' . $indexer . '/results', [
            'query' => ['query' => $query],
        ]);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)['Results'];
    }
}
