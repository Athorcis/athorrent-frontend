<?php

namespace Athorrent\Backend;

use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Filesystem;

trait BackendTrait
{
    private readonly User $user;

    private readonly string $statePath;

    private BackendState $state;

    private ?BackendProcessInterface $process = null;

    protected function initBackend(User $user): void
    {
        $this->user = $user;
        $this->statePath = $user->getBackendPath('state.txt');
        $this->state = $this->readState();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    protected function readState(): BackendState
    {
        $state = @file_get_contents($this->statePath);
        return BackendState::tryFrom($state) ?? BackendState::Unknown;
    }

    public function getState(): BackendState
    {
        return $this->state;
    }

    protected function ensureRunningState(): BackendState
    {
        $state = $this->getState();

        if (!in_array($state, [BackendState::Running, BackendState::Unknown], true)) {
            throw new BackendUnavailableException($state);
        }

        return $state;
    }

    public function setState(BackendState $state): void
    {
        if ($this->state !== $state) {
            $this->state = $state;

            $fs = new Filesystem();
            $fs->dumpFile($this->statePath, $state->value);
        }
    }

    public function getProcess(): ?BackendProcessInterface
    {
        return $this->process;
    }

    public function setProcess(?BackendProcessInterface $process): void
    {
        $this->process = $process;
        $this->setState(BackendState::Running);
    }

    public function __toString(): string
    {
        return sprintf("backend[%d]", $this->user->getId());
    }
}
