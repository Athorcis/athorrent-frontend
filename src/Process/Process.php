<?php

namespace Athorrent\Process;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process as BaseProcess;
use function array_unshift;
use function count;
use function is_array;
use const DIRECTORY_SEPARATOR;

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

    public function start(callable $callback = null, array $env = [])
    {
        if ($this->isDaemon() && is_array($this->getPrivateAttribute('commandline'))) {

            $command = $this->getCommandLine();

            if ('\\' !== DIRECTORY_SEPARATOR) {
                $command = 'exec nohup ' . $command;
            }

            $this->setPrivateAttribute('commandline', $command);
        }

        parent::start($callback, $env);
    }

    /**
     * @param float|null $timeout
     * @return $this
     */
    public function setTimeout(?float $timeout): static
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

    protected function getPrivateAttribute(string $name)
    {
        return Closure::bind(function () use ($name) {
            return $this->$name;
        }, $this, BaseProcess::class)->__invoke();
    }

    protected function setPrivateAttribute(string $name, $value)
    {
        return Closure::bind(function () use ($name, $value) {
            $this->$name = $value;
        }, $this, BaseProcess::class)->__invoke();
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
     * @return string[]
     */
    public static function prefix(array $command): array
    {
        $prefixes = static::getCommandPrefixes();

        if (count($prefixes) > 0) {
            array_unshift($command, ...$prefixes);
        }

        return $command;
    }

    /**
     * @param string[] $command
     * @param string|null $cwd
     * @param array $env
     * @param mixed $input
     * @param bool $daemon
     * @param int|float|null $timeout
     * @return Process
     */
    protected static function new(
        array $command,
        ?string $cwd,
        array $env,
        mixed $input,
        bool $daemon,
        ?float $timeout
    ): Process
    {
        $process = new static(static::prefix($command), $cwd, $env, $input, $daemon ? null : $timeout);

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
     * @param array $env
     * @param mixed $input
     * @param int|float|null $timeout
     * @return Process
     */
    public static function create(array $command, string $cwd = null, array $env = [], mixed $input = null, ?float $timeout = 60): Process
    {
        return static::new($command, $cwd, $env, $input, false, $timeout);
    }

    /**
     * @param string[] $command
     * @param string|null $cwd
     * @param array $env
     * @param mixed $input
     * @return Process
     */
    public static function daemon(array $command, string $cwd = null, array $env = [], mixed $input = null): Process
    {
        return static::new($command, $cwd, $env, $input, true, null);
    }

    /**
     * @param string[] $command
     * @param mixed $input
     * @return string
     */
    public static function exec(array $command, mixed $input = null): string
    {
        $process = static::create($command, null, null, $input);
        $process->mustRun();

        return $process->getOutput();
    }
}
