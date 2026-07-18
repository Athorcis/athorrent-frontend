<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

readonly abstract class AbstractTorrentManager implements TorrentManagerInterface
{
    protected function __construct(protected Filesystem $fs, protected User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
