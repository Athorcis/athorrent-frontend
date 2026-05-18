<?php

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

    public function getTorrentsDirectory(): string
    {
        return $this->user->getNewTorrentsPath();
    }

    protected function makePathRelative(string $path): string
    {
        $backendDir = $this->user->getBackendPath();
        return str_replace($backendDir, '<workdir>', $path);
    }

    protected function makePathAbsolute(string $path): string
    {
        $backendDir = $this->user->getBackendPath();
        return str_replace('<workdir>', $backendDir, $path);
    }
}
