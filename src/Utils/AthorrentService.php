<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Athorrent\Ipc\JsonService;
use Athorrent\Ipc\Socket\NamedPipeClient;
use Athorrent\Ipc\Socket\UnixSocketClient;
use Athorrent\Process\Entity\TrackedProcess;
use Athorrent\Process\Process;
use Athorrent\Process\TrackerProcess;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use const DIRECTORY_SEPARATOR;

class AthorrentService extends JsonService
{
    private $fs;

    private $user;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em, Filesystem $fs, User $user)
    {
        $clientSocketClass = DIRECTORY_SEPARATOR === '\\' ? NamedPipeClient::class : UnixSocketClient::class;

        parent::__construct($clientSocketClass, self::getPath($user->getId()));

        $this->em = $em;
        $this->fs = $fs;
        $this->user = $user;

        $this->ensureRunning();
    }

    private function hasFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $this->user->getId(), $flag]));
    }

    private function setFlag($flag): void
    {
        touch(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $this->user->getId(), $flag]));
    }

    private static function hasGlobalFlag($flag)
    {
        return is_file(implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'flags', $flag]));
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
        $process = $this->user->getAthorrentProcess();

        if ($process && $process->isRunning()) {
            return;
        }

        if ($this->isBusy()) {
            throw new ServiceUnavailableException('SERVICE_UPDATING');
        }

        $this->start();
    }

    private function start(): void
    {
        $logDir = implode(DIRECTORY_SEPARATOR, [BIN_DIR, 'logs', $this->user->getId()]);
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'athorrentd.txt';

        $this->fs->mkdir($logDir, 0755);

        $process = TrackerProcess::track(Process::daemon(['./athorrent-backend', '--user', $this->user->getId()], BIN_DIR));
        $process->start();

        $processEntity = $this->em->find(TrackedProcess::class,  $process->getTrackedId());
        $this->user->setAthorrentProcess($processEntity);
        $this->em->flush();
    }

    private static function getPath($userId): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return '\\\\.\\pipe\\athorrentd\\sockets\\' . $userId . '.sck';
        }

        return BIN_DIR . '/sockets/' . $userId . '.sck';
    }
}
