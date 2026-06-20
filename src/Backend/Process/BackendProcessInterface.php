<?php

declare(strict_types=1);

namespace Athorrent\Backend\Process;

interface BackendProcessInterface
{
    public function isRunning(): bool;

    public function stop(): void;

    public function shouldRestartToUpdate(): bool;

    public function getErrorInfo(): array;
}
