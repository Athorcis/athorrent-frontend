<?php

namespace Athorrent\Process;

use Athorrent\Process\Command\TrackProcessCommand;
use RuntimeException;
use function array_merge;
use function array_shift;
use function json_encode;
use function preg_match;
use const JSON_THROW_ON_ERROR;

class TrackerProcess extends CommandProcess
{
    private $trackedId;

    /**
     * @param callable|null $callback
     * @param array $env
     */
    public function start(callable $callback = null, array $env = []): void
    {
        if ($this->isOutputDisabled()) {
            $this->enableOutput();
        }

        parent::start($callback, $env);

        $this->waitUntil(
            function ($type, $message) {
                dump($message);
                if ($type === self::OUT && preg_match('/^id:(\d+)$/', $message, $matches)) {
                    $this->trackedId = (int)$matches[1];
                }

                return true;
            }
        );
    }

    public function getTrackedId()
    {
        return $this->trackedId;
    }

    public static function getCommandPrefixes(): array
    {
        return array_merge(parent::getCommandPrefixes(), [TrackProcessCommand::NAME]);
    }

    public static function prefix(array $command, bool $nohup = false): array
    {
        return parent::prefix([json_encode($command, JSON_THROW_ON_ERROR)], $nohup);
    }

    /**
     * @param Process $process
     * @return static
     */
    public static function track(Process $process): self
    {
        if ($process->isStarted()) {
            throw new RuntimeException('cannot track an already started process');
        }

        $isDaemon = $process->isDaemon();
        $method = $isDaemon? 'daemon' : 'create';
        $commandLine = $process->getCommandLineArray();

        // It's useless to use nohup twice
        if ($isDaemon && $commandLine[0] === 'nohup') {
            array_shift($commandLine);
        }

        $tracker = static::$method($commandLine, $process->getWorkingDirectory(), $process->getEnv());
        $tracker->setTimeout($process->getTimeout());

        return $tracker;
    }
}
