<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    /** @var TorrentManager[] */
    private array $instances = [];

    public function __construct(private readonly EntityManagerInterface $em, private readonly Filesystem $fs)
    {
    }

    public function create(User $user): TorrentManager
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = new TorrentManager($this->em, $this->fs, $user);
        }

        return $this->instances[$userId];
    }
}
