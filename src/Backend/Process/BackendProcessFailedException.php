<?php

declare(strict_types=1);

namespace Athorrent\Backend\Process;

use Athorrent\Backend\BackendInterface;
use Exception;

/**
 * @phpstan-import-type BackendProcessErrorInfo from BackendProcessInterface
 */
class BackendProcessFailedException extends Exception
{
    /**
     * @param BackendProcessErrorInfo $errorInfo
     */
    public function __construct(string $message, private readonly BackendInterface $backend, private readonly array $errorInfo)
    {
        parent::__construct($message);
    }

    public function getBackend(): BackendInterface
    {
        return $this->backend;
    }

    /**
     * @return BackendProcessErrorInfo
     */
    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }
}
