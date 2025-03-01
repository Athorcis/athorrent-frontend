<?php

namespace Athorrent\Backend\Process;

interface BackendProcessInterface
{
    public function isRunning(): bool;

    public function stop(): void;

    public function getErrorInfo(): array;
}
