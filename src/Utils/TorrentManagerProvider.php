<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;

class TorrentManagerProvider
{
    private $instances;

    public function __construct()
    {
        $this->instances = [];
    }

    public function get(User $user)
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = new TorrentManager($user);
        }

        return $this->instances[$userId];
    }
}
