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
        $clientType = $user->getClientType();

        if ($clientType === User::CLIENT_TYPE_LEGACY) {
            return new LegacyBackend($user);
        }

        if ($clientType === User::CLIENT_TYPE_QBITTORRENT) {
            return new QBittorrentBackend($this->cache, $this->http, $user);
        }

        throw new \RuntimeException(sprintf('Unsupported client type "%s"', $clientType));
    }

    public function create(User $user): BackendInterface
    {
        $userId = $user->getId();
        $clientType = $user->getClientType();
        $key = $clientType . '_' . $userId;

        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->doCreate($user);
        }

        return $this->instances[$key];
    }
}
