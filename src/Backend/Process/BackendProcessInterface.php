<?php

declare(strict_types=1);

namespace Athorrent\Backend\Process;

/**
 * @phpstan-type BackendProcessErrorInfo array{state: string, logs: string}
 */
interface BackendProcessInterface
{
    public function isRunning(): bool;

    public function stop(): void;

    public function shouldRestartToUpdate(): bool;

    /** @return BackendProcessErrorInfo */
    public function getErrorInfo(): array;
}
