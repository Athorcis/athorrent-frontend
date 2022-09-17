<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    /** @var TorrentManager[] */
    private array $instances = [];

    public function __construct(private EntityManagerInterface $em, private Filesystem $fs)
    {
    }

    public function create(User $user)
    {
        $userId = $user->getId();

        if (!isset($this->instances[$userId])) {
            $this->instances[$userId] = new TorrentManager($this->em, $this->fs, $user);
        }

        return $this->instances[$userId];
    }
}
