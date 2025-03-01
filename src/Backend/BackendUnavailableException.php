<?php

namespace Athorrent\Backend;

use Exception;
use Throwable;

class BackendUnavailableException extends Exception
{
    public function __construct(readonly BackendState $state, Throwable $previous = null)
    {
        parent::__construct(sprintf('backend is unavailable [state=%s]', $this->state->value), 0, $previous);
    }
}
