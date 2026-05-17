<?php

namespace Athorrent\Backend;

use Athorrent\Database\Entity\User;
use Athorrent\Utils\QBittorrentClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QBittorrentBackend implements BackendInterface
{
    use BackendTrait;

    private QBittorrentClient $client;

    public function __construct(HttpClientInterface $http, User $user)
    {
        $this->client = new QBittorrentClient($http, $user);
        $this->initBackend($user);
    }

    public function request(string $method, string $path, array $options = [])
    {
        $state = $this->ensureRunningState();

        try {
            return $this->client->request($method, $path, $options);
        }
        catch (ExceptionInterface $e) {
            throw new BackendUnavailableException($state, $e);
        }
    }

    public function ping(): bool
    {
        try {
            $response = $this->client->request('GET', '/api/v2/app/version');
            return $response->getStatusCode() === 200;
        }
        catch (ExceptionInterface $e) {
            return false;
        }
    }

    public function clean(): void
    {
        // TODO: Implement clean() method.
    }
}
