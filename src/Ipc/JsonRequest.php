<?php

namespace Athorrent\Ipc;

class JsonRequest
{
    private string $action;

    private array $parameters;

    public function __construct(string $action, array $parameters)
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
