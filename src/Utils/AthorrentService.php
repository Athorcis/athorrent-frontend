<?php

namespace Athorrent\Utils;

use Athorrent\Ipc\JsonService;

class AthorrentService extends JsonService
{
    private $userId;

    public function __construct($userId)
    {
        switch (strtolower(PHP_OS)) {
            case 'unix':
                $socketType = 'UnixSocket';
                break;

            case 'winnt':
                $socketType = 'NamedPipe';
                break;

            default:
                throw new \RuntimeException('unsuported system: ' . PHP_OS);
        }

        parent::__construct('Athorrent\\Ipc\\Socket\\' . $socketType . 'Client', self::getPath($userId));

        $this->userId = $userId;
        $this->ensureRunning();
    }

    private function hasFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $this->userId, $flag]));
    }

    private function setFlag($flag)
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

    private function ensureRunning()
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

    private function start()
    {
        $logDir = implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'logs', $this->userId]);
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'athorrentd.txt';

        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        if (is_file($logPath)) {
            $nbLogs = count(scandir($logDir)) - 2;

            if ($nbLogs) {
                if (!@rename($logPath, $logDir . DIRECTORY_SEPARATOR . 'athorrentd.' . $nbLogs . '.txt')) {
                    try {
                        if ($this->call('ping') === 'pong') {
                            $this->setFlag('running');
                            return;
                        }
                    } catch (ServiceUnavailableException $exception) {
                        exit('log file appears to be locked');
                    }
                }
            }
        }

        $cwd = BIN_DIR;
        $cmd = 'athorrent-backend --user ' . $this->userId;

        switch (strtolower(PHP_OS)) {
            case 'linux':
                $cmd = '(cd ' . $cwd . ' && ./' . $cmd . ') > ' . $logPath . ' 2>&1 &';
                break;

            case 'winnt':
                // If directory path contains slashes instead of antislashes
                // then it doesn't work
                $cwd = str_replace('/', '\\', $cwd);
                $cmd = 'start /D ' . $cwd . ' /B ' . $cmd . ' > ' . $logPath;
                break;
        }

        exec($cmd);
    }

    private static function getPath($userId)
    {
        switch (strtolower(PHP_OS)) {
            case 'linux':
                $path = BIN_DIR . '/sockets/' . $userId . '.sck';
                break;

            case 'winnt':
                $path = '\\\\.\\pipe\\athorrentd\\sockets\\' . $userId . '.sck';
                break;

            default:
                $path = null;
        }

        return $path;
    }
}
