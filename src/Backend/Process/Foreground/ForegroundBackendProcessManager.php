<?php

namespace Athorrent\Backend\Process\Foreground;

use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class ForegroundBackendProcessManager implements BackendProcessManagerInterface
{
    /** @var array<int, ForegroundBackendProcess>  */
    private array $processes = [];

    public function isPersistent(): bool
    {
        return false;
    }

    public function create(User $user): BackendProcessInterface
    {
        $process = new Process([$_ENV['BACKEND_FOREGROUND_BINARY'], '--port', $user->getPort()], $user->getBackendPath());
        $process->start();

        return new ForegroundBackendProcess($process);
    }

    public function clean(User $user): void
    {
        $userId = $user->getId();

        if (isset($this->processes[$userId])) {
            $this->processes[$userId]->stop();
            unset($this->processes[$userId]);
        }
    }

    public function listRunningProcesses(): array
    {
        return [];
    }

    public static function getType(): string
    {
        return 'foreground';
    }
}
