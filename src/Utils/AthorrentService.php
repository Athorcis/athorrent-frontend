<?php

namespace Athorrent\Utils;

use Athorrent\IPC\JsonService;

class AthorrentService extends JsonService
{
    private $userId;

    public function __construct($userId)
    {
        parent::__construct('Athorrent\IPC\LocalClientSocket_' . strtolower(PHP_OS), self::getPath($userId));

        $this->userId = $userId;
        $this->ensureRunning();
    }

    private function hasFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, array(BIN, 'flags', $this->userId, $flag)));
    }

    private function setFlag($flag)
    {
        touch(implode(DIRECTORY_SEPARATOR, array(BIN, 'flags', $this->userId, $flag)));
    }

    private static function hasGlobalFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, array(BIN, 'flags', $flag)));
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
            throw ServiceUnvailableException('SERVICE_UPDATING');
        }

        $this->start();

        do {
            usleep(100000);
        } while (!$this->isRunning());
    }

    private function start()
    {
        $logDir = BIN . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $this->userId;
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

        $cmd = 'athorrentd --user ' . $this->userId;

        switch (strtolower(PHP_OS)) {
            case 'linux':
                $cmd = '(cd ' . BIN . ' && ./' . $cmd . ') &> ' . $logPath . ' &';
                break;

            case 'winnt':
                $cmd = 'start /D ' . BIN . ' /B ' . $cmd . ' > ' . $logPath;
                break;
        }

        exec($cmd);
    }

    private static function getPath($userId)
    {
        switch (strtolower(PHP_OS)) {
            case 'linux':
                $path = BIN . '/sockets/' . $userId . '.sck';
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
