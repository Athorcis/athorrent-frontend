<?php

namespace Athorrent\Utils;

use Exception;

class ServiceUnavailableException extends Exception
{
    private $reason;

    public function __construct($reason)
    {
        parent::__construct('service is unavailable: ' . $reason);
        $this->reason = $reason;
    }

    public function getReason()
    {
        return $this->reason;
    }
}
