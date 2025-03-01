<?php

namespace Athorrent\Backend\Process\Foreground;

use Athorrent\Backend\Process\BackendProcessInterface;
use Symfony\Component\Process\Process;

readonly class ForegroundBackendProcess implements BackendProcessInterface
{
    public function __construct(private Process $process)
    {
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function shouldRestartToUpdate(): bool
    {
        return false;
    }

    public function getErrorInfo(): array
    {
        return [
            'status' => $this->process->getStatus(),
            'exitCode' => $this->process->getExitCode(),
            'output' => $this->process->getOutput(),
            'stderr' => $this->process->getErrorOutput(),
        ];
    }
}
