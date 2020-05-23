<?php

namespace Athorrent\Utils;

use Athorrent\Ipc\JsonService;
use Athorrent\Ipc\Socket\NamedPipeClient;
use Athorrent\Ipc\Socket\UnixSocketClient;
use Symfony\Component\Filesystem\Filesystem;
use const DIRECTORY_SEPARATOR;

class AthorrentService extends JsonService
{
    private $fs;

    private $userId;

    public function __construct(Filesystem $fs, $userId)
    {
        $clientSocketClass = DIRECTORY_SEPARATOR === '\\' ? NamedPipeClient::class : UnixSocketClient::class;

        parent::__construct($clientSocketClass, self::getPath($userId));

        $this->fs = $fs;
        $this->userId = $userId;
        $this->ensureRunning();
    }

    private function hasFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $this->userId, $flag]));
    }

    private function setFlag($flag): void
    {
        touch(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $this->userId, $flag]));
    }

    private static function hasGlobalFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $flag]));
    }

    private function isRunning()
    {
        return $this->hasFlag('running');
    }

    private static function isUpdating()
    {
        return self::hasGlobalFlag('updating');
    }

    private function isBusy()
    {
        return self::isUpdating();
    }

    private function ensureRunning(): void
    {
        if ($this->isRunning()) {
            return;
        }

        if ($this->isBusy()) {
            throw new ServiceUnavailableException('SERVICE_UPDATING');
        }

        $this->start();

        do {
            usleep(100000);
        } while (!$this->isRunning());
    }

    private function start(): void
    {
        $logDir = implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'logs', $this->userId]);
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'athorrentd.txt';

        $this->fs->mkdir($logDir, 0755);

        $process = Process::daemon(['athorrent-backend', '--user', $this->userId], BIN_DIR);
        $process->start();
    }

    private static function getPath($userId): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return '\\\\.\\pipe\\athorrentd\\sockets\\' . $userId . '.sck';
        }

        return BIN_DIR . '/sockets/' . $userId . '.sck';
    }
}
