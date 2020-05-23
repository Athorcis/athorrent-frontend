<?php

namespace Athorrent\Utils;

use RuntimeException;
use Symfony\Component\Process\Process as BaseProcess;
use function array_unshift;

class Process extends BaseProcess
{
    private $daemon = false;

    public function __destruct()
    {
        // Si le processus n'est pas un daemon
        if (!$this->isDaemon()) {

            // On le stoppe:
            // on appelle directement la méthode stop au lieu du destructeur parent
            // pour laisser le temps au processus de se fermer avant d'envoyer un SIGKILL
            $this->stop();
        }
    }

    /**
     * @param float|int|null $timeout
     * @return BaseProcess
     */
    public function setTimeout($timeout): BaseProcess
    {
        if ($this->daemon && $timeout > 0) {
            throw new RuntimeException('a daemon process cannot have a timeout');
        }

        return parent::setTimeout($timeout);
    }

    /**
     * @return bool
     */
    public function isDaemon(): bool
    {
        return $this->daemon;
    }

    /**
     * @return string[]
     */
    protected static function getCommandPrefixes(): array
    {
        return [];
    }

    /**
     * @param string[] $command
     * @param bool $nohup
     * @return string[]
     */
    public static function prefix(array $command, bool $nohup = false): array
    {
        $prefixes = static::getCommandPrefixes();

        if (count($prefixes) > 0) {
            array_unshift($command, ...$prefixes);
        }

        if ($nohup && strtolower(PHP_OS) === 'linux') {
            array_unshift($command, 'nohup');
        }

        return $command;
    }

    /**
     * @param string[] $command
     * @param string|null $cwd
     * @param array|null $env
     * @param mixed|null $input
     * @param bool $daemon
     * @param int|float|null $timeout
     * @return Process
     */
    protected static function new(
        array $command,
        ?string $cwd,
        ?array $env,
        $input,
        bool $daemon,
        ?float $timeout
    ): Process
    {
        $process = new static(static::prefix($command, $daemon), $cwd, $env, $input, $daemon ? null : $timeout);

        $process->daemon = $daemon;

        // Il arrive que quand la sortie n'est pas désactivée le processus se fasse tuer à la fin de la requête
        // bizarrement ça ne semble pas se produire avec la classe TrackerProcess
        if ($daemon) {
            $process->disableOutput();
        }

        return $process;
    }

    /**
     * @param string[] $command
     * @param string|null $cwd
     * @param array|null $env
     * @param mixed|null $input
     * @param int|float|null $timeout
     * @return Process
     */
    public static function create(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): Process
    {
        return static::new($command, $cwd, $env, $input, false, $timeout);
    }

    /**
     * @param string[] $command
     * @param string|null $cwd
     * @param array|null $env
     * @param mixed|null $input
     * @return Process
     */
    public static function daemon(array $command, string $cwd = null, array $env = null, $input = null): Process
    {
        return static::new($command, $cwd, $env, $input, true, null);
    }

    /**
     * @param string[] $command
     * @param mixed|null $input
     * @return string
     */
    public static function exec(array $command, $input = null): string
    {
        $process = static::create($command, null, null, $input);
        $process->mustRun();

        return $process->getOutput();
    }
}
