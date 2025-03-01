<?php

namespace Athorrent\Backend\Process;

use Athorrent\Backend\Backend;
use Exception;

class BackendProcessFailedException extends Exception
{
    public function __construct(string $message, private readonly Backend $backend, private readonly array $errorInfo)
    {
        parent::__construct($message);
    }

    public function getBackend(): Backend
    {
        return $this->backend;
    }

    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }
}
