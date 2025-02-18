<?php

namespace Athorrent\Process;

use Athorrent\Process\Command\TrackProcessCommand;
use RuntimeException;
use function array_merge;
use function json_encode;
use function preg_match;
use const JSON_THROW_ON_ERROR;

class TrackerProcess extends CommandProcess
{
    private int $trackedId;

    public function start(callable|null $callback = null, array $env = []): void
    {
        if ($this->isOutputDisabled()) {
            $this->enableOutput();
        }

        parent::start($callback, $env);

        $this->waitUntil(
            function ($type, $message) {
                if ($type === self::OUT && preg_match('/^id:(\d+)$/', $message, $matches)) {
                    $this->trackedId = (int)$matches[1];
                }

                return true;
            }
        );
    }

    public function getTrackedId(): int
    {
        return $this->trackedId;
    }

    public static function getCommandPrefixes(): array
    {
        return array_merge(parent::getCommandPrefixes(), [TrackProcessCommand::NAME]);
    }

    public static function prefix(array $command): array
    {
        return parent::prefix([json_encode($command, JSON_THROW_ON_ERROR)]);
    }

    public static function track(Process $process): self
    {
        if ($process->isStarted()) {
            throw new RuntimeException('cannot track an already started process');
        }

        $method = $process->isDaemon() ? 'daemon' : 'create';
        $command = $process->getPrivateAttribute('commandline');

        $tracker = static::$method($command, $process->getWorkingDirectory(), $process->getEnv());
        $tracker->setTimeout($process->getTimeout());

        return $tracker;
    }
}
