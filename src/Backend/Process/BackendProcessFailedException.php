<?php

namespace Athorrent\Backend\Process;

use Athorrent\Backend\BackendInterface;
use Exception;

class BackendProcessFailedException extends Exception
{
    public function __construct(string $message, private readonly BackendInterface $backend, private readonly array $errorInfo)
    {
        parent::__construct($message);
    }

    public function getBackend(): BackendInterface
    {
        return $this->backend;
    }

    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }
}
