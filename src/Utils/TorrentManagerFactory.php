<?php

namespace Athorrent\Utils;

use Athorrent\Backend\BackendFactory;
use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    /** @var TorrentManagerInterface[] */
    private array $instances = [];

    public function __construct(
        private readonly Filesystem $fs,
        private readonly BackendFactory $backendFactory,
    )
    {}

    protected function doCreate(User $user): TorrentManagerInterface
    {
        $clientType = $user->getClientType();
        $backend = $this->backendFactory->create($user);

        if ($clientType === User::CLIENT_TYPE_LEGACY) {
            return new TorrentManager($this->fs, $user, $backend);
        }

        if ($clientType === User::CLIENT_TYPE_QBITTORRENT) {
            return new QBittorrentManager($this->fs, $user, $backend);
        }

        throw new \RuntimeException(sprintf('Unsupported client type "%s"', $clientType));
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
