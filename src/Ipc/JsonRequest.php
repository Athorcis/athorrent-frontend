<?php

namespace Athorrent\Ipc;

readonly class JsonRequest
{
    public function __construct(private string $action, private array $parameters)
    {
    }

    public function toRawRequest(): string
    {
        return json_encode(
                ['action' => $this->action, 'parameters' => $this->parameters],
                JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT
            ). "\n";
    }
}
