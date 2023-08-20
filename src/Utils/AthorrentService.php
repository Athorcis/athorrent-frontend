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
use Symfony\Component\Filesystem\Path;
use const DIRECTORY_SEPARATOR;

class AthorrentService extends JsonService
{

    public function __construct(private readonly EntityManagerInterface $em, private readonly Filesystem $fs, private readonly User $user)
    {
        $clientSocketClass = DIRECTORY_SEPARATOR === '\\' ? NamedPipeClient::class : UnixSocketClient::class;

        parent::__construct($clientSocketClass, self::getPath($user));

        if ($_ENV['BACKEND_AUTO_START']) {
            $this->ensureRunning();
        }
    }

    private function ensureRunning(): void
    {
        $process = $this->user->getAthorrentProcess();

        if ($process && $process->isRunning()) {
            return;
        }

        $this->start();
    }

    private function start(): void
    {
        $logDir = $this->user->getBackendPath('logs');
        $logPath = Path::join($logDir, 'athorrentd.txt');

        $this->fs->mkdir($logDir, 0755);

        $process = TrackerProcess::track(Process::daemon([Path::join(BIN_DIR, 'athorrent-backend'), '--port', $this->user->getPort()], $this->user->getBackendPath()));
        $process->start();

        $processEntity = $this->em->find(TrackedProcess::class,  $process->getTrackedId());
        $this->user->setAthorrentProcess($processEntity);
        $this->em->flush();
    }

    private static function getPath(User $user): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return '\\\\.\\pipe\\athorrentd\\sockets\\' . $user->getPort() . '.sck';
        }

        return $user->getBackendPath('athorrentd.sck');
    }
}
