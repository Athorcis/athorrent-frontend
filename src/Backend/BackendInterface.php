<?php

namespace Athorrent\Backend;

use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Database\Entity\User;

interface BackendInterface
{

    public function getUser(): User;

    public function getState(): BackendState;
    public function setState(BackendState $state): void;

    public function getProcess(): ?BackendProcessInterface;
    public function setProcess(?BackendProcessInterface $process): void;

    public function ping(): bool;

    public function clean(): void;

}
