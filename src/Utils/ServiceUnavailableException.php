<?php

namespace Athorrent\Utils;

use Exception;

class ServiceUnavailableException extends Exception
{
    private readonly string $reason;

    public function __construct(string $reason)
    {
        parent::__construct('service is unavailable: ' . $reason);
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
