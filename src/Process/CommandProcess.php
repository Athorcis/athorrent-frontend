<?php

namespace Athorrent\Process;

use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;

class CommandProcess extends Process
{
    private static $consolePath;

    private static $commandPrefixes;

    protected static function getCommandPrefixes(): array
    {
        if (self::$commandPrefixes === null) {
            if (self::$consolePath === null) {
                throw new RuntimeException('you must set the console path before using this class');
            }

            $phpExecutableFinder = new PhpExecutableFinder();
            $php = $phpExecutableFinder->find();

            if ($php === false) {
                throw new RuntimeException('unable to find php executable');
            }

            self::$commandPrefixes = [$php, self::$consolePath];
        }

        return self::$commandPrefixes;
    }

    /**
     * @param string $path
     */
    public static function setConsolePath(string $path): void
    {
        self::$consolePath = $path;
    }
}
