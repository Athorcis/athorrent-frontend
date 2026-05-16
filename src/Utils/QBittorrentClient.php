<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class QBittorrentClient
{
    private string $baseUrl;

    private const CACHE_NAMESPACE = 'qb_client';
    private const SID_CACHE_PREFIX = 'qb_sid_';

    public function __construct(private HttpClientInterface $http, private User $user)
    {
    }

    protected function getBaseUrl(): string
    {
        return  'http://' . ($this->user->getClientIp() ?? 'host.docker.internal') . ':8080';
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     * @throws ExceptionInterface
     */
    protected function doRequest(string $method, string $url, array $options): ResponseInterface
    {
        $options['headers']['Cookie'] = 'SID=' . $this->getSid();
        return $this->http->request($method, $url, $options);
    }

    /**
     * @throws ExceptionInterface
     */
    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        $this->baseUrl = $this->getBaseUrl();
        $url = $this->baseUrl . $path;

        $response = $this->doRequest($method, $url, $options);

        if ($response->getStatusCode() === 403) {
            $this->clearSidCache();
            $response = $this->doRequest($method, $url, $options);
        }

        return $response;
    }

    /**
     * @throws ExceptionInterface
     */
    private function getSid(): string
    {
        $cache = new FilesystemAdapter(self::CACHE_NAMESPACE);
        $cacheKey = self::SID_CACHE_PREFIX . $this->user->getId();
        $item = $cache->getItem($cacheKey);

        if ($item->isHit()) {
            return (string) $item->get();
        }

        $response = $this->http->request('POST', $this->baseUrl . '/api/v2/auth/login');

        $cookies = $response->getHeaders(false)['set-cookie'] ?? [];

        foreach ($cookies as $cookie) {
            if (preg_match('/SID=([^;]+)/', $cookie, $matches) === 1) {
                $sid = $matches[1];
                $item->set($sid);
                $item->expiresAfter(60 * 30);
                $cache->save($item);

                return $sid;
            }
        }

        throw new Exception('Unable to retrieve qBittorrent SID.');
    }

    private function clearSidCache(): void
    {
        $cache = new FilesystemAdapter(self::CACHE_NAMESPACE);
        $cacheKey = self::SID_CACHE_PREFIX . $this->user->getId();
        $cache->deleteItem($cacheKey);
    }
}
