<?php

namespace Athorrent\Backend\Process\Docker;

use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use Clue\React\Docker\Client;
use Psr\Log\LoggerInterface;
use React\Http\Message\ResponseException;
use function React\Async\await;

class DockerBackendProcessManager implements BackendProcessManagerInterface
{
    /** @var array<int, DockerBackendProcess>  */
    private array $processes = [];

    public function __construct(private readonly Client $docker, private readonly LoggerInterface $logger) {}

    public function isPersistent(): bool
    {
        return true;
    }

    public function listRunningProcesses(): array
    {
        return $this->listProcesses(false);
    }

    /**
     * @param int[] $userIds
     * @return DockerBackendProcess[]
     * @throws \Throwable
     */
    protected function listProcesses(bool $all, array $userIds = []): array
    {
        $clients = [];

        $containers = await($this->docker->containerList($all));

        foreach ($containers as $container) {
            $userId = null;

            if (isset($container['Labels']['com.athorrent.user'])) {
                $userId = (int)$container['Labels']['com.athorrent.user'];
            }
            else {
                foreach ($container['Names'] as $name) {
                    if (str_starts_with($name, '/athorrentd_')) {
                        $userId = (int)str_replace('/athorrentd_', '', $name);
                        break;
                    }
                }
            }

            if ($userId && (count($userIds) === 0 || in_array($userId, $userIds, true))) {
                $clients[$userId] = new DockerBackendProcess($this->docker, $container['Id']);
            }
        }

        return $clients;
    }

    protected function pullImage(string $image): void
    {
        $this->logger->info(sprintf('Pulling image %s...', $image));
        await($this->docker->imageCreate($image));
    }

    protected function pullImageIfNotExists(string $image): void
    {
        try {
            await($this->docker->imageInspect($image));
        }
        catch (ResponseException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }

            $this->pullImage($image);
        }
    }

    public function create(User $user): DockerBackendProcess
    {
        $userId = $user->getId();
        $port = $user->getPort();
        $image = $_ENV['BACKEND_DOCKER_IMAGE'];

        $this->pullImageIfNotExists($image);

        $container = await($this->docker->containerCreate([
            'Image' => $image,
            'Cmd' => ['--port', "$port"],
            'User' => 'www-data',
            'WorkingDir' => '/var/lib/athorrent-backend',
            'Healthcheck' => [
                'Test' => ["NONE"]
            ],
            'HostConfig' => [
                'NetworkMode' => 'host',
                'Mounts' => [
                    [
                        'Type' => 'bind',
                        'Source' => $_ENV['BACKEND_DOCKER_DATA_SRC'] . '/' . $userId . '/backend',
                        'Target' => '/var/lib/athorrent-backend',
                        'ReadOnly' => false,
                    ]
                ]
            ],
            'Labels' => [
                'com.athorrent.user' => "$userId",
            ]
        ], 'athorrentd_' . $userId));

        await($this->docker->containerStart($container['Id']));

        return new DockerBackendProcess($this->docker, $container['Id']);
    }

    protected function doClean(DockerBackendProcess $process, int $userId): void
    {
        try {
            if ($process->isRunning()) {
                $process->stop();
            }

            $process->remove();
        }
        finally {
            unset($this->processes[$userId]);
        }
    }

    public function clean(User $user): void
    {
        $userId = $user->getId();

        if (isset($this->processes[$userId])) {
            $process = $this->processes[$userId];
        }
        else {
            $processes = $this->listProcesses(true, [$userId]);

            if (isset($processes[$userId])) {
                $process = $processes[$userId];
            }
        }

        if (isset($process)) {
            $this->doClean($process, $userId);
        }
    }

    public static function getType(): string
    {
        return 'docker';
    }
}
