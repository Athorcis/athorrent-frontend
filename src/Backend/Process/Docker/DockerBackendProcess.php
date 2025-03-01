<?php

namespace Athorrent\Backend\Process\Docker;

use Athorrent\Backend\Process\BackendProcessInterface;
use Clue\React\Docker\Client;
use React\Http\Message\ResponseException;
use function React\Async\await;

readonly class DockerBackendProcess implements BackendProcessInterface
{
    public function __construct(private Client $docker, private string $containerId)
    {
    }

    public function isRunning(): bool
    {
        try {
            $data = await($this->docker->containerInspect($this->containerId));
            return $data['State']['Status'] === 'running';
        }
        catch (ResponseException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }

        return false;
    }

    public function stop(): void
    {
        await($this->docker->containerStop($this->containerId));
    }

    public function getErrorInfo(): array
    {
        $data = await($this->docker->containerInspect($this->containerId));

        $logs = await($this->docker->containerLogs($this->containerId, false, true, true, 0, false, 25));

        return [
            'state' => $data['State'],
            'logs' => $logs,
        ];
    }

    public function remove(): void
    {
        await($this->docker->containerRemove($this->containerId));
    }
}
