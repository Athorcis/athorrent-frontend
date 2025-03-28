<?php

namespace Athorrent\Backend\Process\Foreground;

use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

class ForegroundBackendProcessManager implements BackendProcessManagerInterface
{
    /** @var array<int, ForegroundBackendProcess>  */
    private array $processes = [];

    public function __construct(
        #[Autowire('%env(resolve:BACKEND_FOREGROUND_BINARY)%')]
        private readonly string $binaryPath,
    ) {}

    public function isPersistent(): bool
    {
        return false;
    }

    public function supportsUpdate(): bool
    {
        return false;
    }

    public function requestUpdate(): void
    {
        throw new RuntimeException('Update not supported');
    }

    public function create(User $user): BackendProcessInterface
    {
        $process = new Process([$this->binaryPath, '--port', $user->getPort()], $user->getBackendPath());
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
