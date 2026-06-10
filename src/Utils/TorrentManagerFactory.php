<?php

namespace Athorrent\Utils;

use Athorrent\Backend\BackendFactory;
use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;

class TorrentManagerFactory
{
    /** @var TorrentManagerInterface[] */
    private array $instances = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Filesystem $fs,
        private readonly BackendFactory $backendFactory,
    )
    {}

    protected function doCreate(User $user): TorrentManagerInterface
    {
        $backend = $this->backendFactory->create($user);
        return new QBittorrentManager($this->cache, $this->fs, $user, $backend);
    }

    public function create(User $user): TorrentManagerInterface
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = $this->doCreate($user);
        }

        return $this->instances[$userId];
    }
}
