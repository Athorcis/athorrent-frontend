<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    private $fs;

    private $instances;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
        $this->instances = [];
    }

    public function create(User $user)
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = new TorrentManager($this->fs, $user);
        }

        return $this->instances[$userId];
    }
}
