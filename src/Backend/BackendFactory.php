<?php

namespace Athorrent\Backend;

use Athorrent\Database\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BackendFactory
{
    private array $instances = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $http)
    {}

    protected function doCreate(User $user): BackendInterface
    {
        return new QBittorrentBackend($this->cache, $this->http, $user);
    }

    public function create(User $user): BackendInterface
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = $this->doCreate($user);
        }

        return $this->instances[$userId];
    }

    public function remove(User $user): void
    {
        $userId = $user->getId();

        if (isset($this->instances[$userId])) {
            unset($this->instances[$userId]);
        }
    }
}
