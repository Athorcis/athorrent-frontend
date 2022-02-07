<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class TorrentManagerFactory
{
    private EntityManagerInterface $em;

    private Filesystem $fs;

    /** @var TorrentManager[] */
    private array $instances;

    public function __construct(EntityManagerInterface $em, Filesystem $fs)
    {
        $this->em = $em;
        $this->fs = $fs;
        $this->instances = [];
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
