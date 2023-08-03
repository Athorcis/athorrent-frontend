<?php

namespace Athorrent\Utils\Search;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class JackettApi
{
    private ?Cookie $authCookie = null;

    public function __construct(private HttpClientInterface $http) {}

    protected function sendRequest(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->http->request($method, $_ENV['JACKETT_ORIGIN'] . $path, $options);
    }

    protected function login(): void
    {
        $response = $this->sendRequest('POST', '/UI/Dashboard', [
            'body' => ['password' => $_ENV['JACKET_ADMIN_PASSWORD']],
            'max_redirects' => 0,
        ]);

        $this->authCookie = Cookie::fromString($response->getHeaders(false)['set-cookie'][0]);
    }

    protected function getCookieHeader(): string
    {
        if ($this->authCookie === null) {
            $this->login();
        }

        return $this->authCookie->getName() . '=' . $this->authCookie->getValue();
    }

    protected function queryInternalApi(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->sendRequest($method, $path, array_merge_recursive($options, [
            'headers' => ['Cookie' => $this->getCookieHeader()],
        ]));
    }

    protected function queryExternalApi(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->sendRequest($method, $path, array_merge_recursive($options, [
            'query' => ['apikey' => $_ENV['JACKET_API_KEY']],
        ]));
    }

    public function getIndexers($query = [])
    {
        $response = $this->queryInternalApi('GET', '/api/v2.0/indexers', [
            'query' => $query,
        ]);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getConfiguredIndexers()
    {
        return $this->getIndexers(['configured' => 'true']);
    }

    public function getResults(string $query, $indexer = 'all')
    {
        $response = $this->queryExternalApi('GET', '/api/v2.0/indexers/' . $indexer . '/results', [
            'query' => ['query' => $query],
        ]);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)['Results'];
    }
}
