<?php

namespace Athorrent\Ipc;

class JsonRequest
{
    private $action;

    private $parameters;

    public function __construct($action, $parameters)
    {
        $this->action = $action;
        $this->parameters = $parameters;
    }

    public function toRawRequest(): string
    {
        return json_encode(
                ['action' => $this->action, 'parameters' => $this->parameters],
                JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT
            ). "\n";
    }
}
