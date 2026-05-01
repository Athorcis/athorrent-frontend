<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    /** @var TorrentManagerInterface[] */
    private array $instances = [];

    public function __construct(private readonly Filesystem $fs)
    {
    }

    public function create(User $user): TorrentManagerInterface
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = new TorrentManager($this->fs, $user);
        }

        return $this->instances[$userId];
    }
}
