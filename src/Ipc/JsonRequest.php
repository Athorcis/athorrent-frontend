<?php

namespace Athorrent\Ipc;

use JsonException;

readonly class JsonRequest
{
    public function __construct(private string $action, private array $parameters)
    {
    }

    /**
     * @return string
     * @throws JsonException
     */
    public function toRawRequest(): string
    {
        return json_encode(
                ['action' => $this->action, 'parameters' => $this->parameters],
                JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT
            ). "\n";
    }
}
