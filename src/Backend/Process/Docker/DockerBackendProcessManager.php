<?php

namespace Athorrent\Backend\Process\Docker;

use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use Clue\React\Docker\Client;
use Psr\Log\LoggerInterface;
use React\Http\Message\ResponseException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;
use function React\Async\await;

class DockerBackendProcessManager implements BackendProcessManagerInterface
{
    /** @var array<int, DockerBackendProcess>  */
    private array $processes = [];

    public function __construct(
        private readonly Client $docker,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(BACKEND_DOCKER_IMAGE)%')]
        private string $imageTag,
        #[Autowire('%env(BACKEND_DOCKER_MOUNT_TYPE)%')]
        private string $mountType,
        #[Autowire('%env(BACKEND_DOCKER_MOUNT_SRC)%')]
        private string $mountSrc
    )
    {}

    public function isPersistent(): bool
    {
        return true;
    }

    public function supportsUpdate(): bool
    {
        return true;
    }

    public function requestUpdate(): void
    {
        $this->pullImage($this->imageTag);
        $imageId = await($this->docker->imageInspect($this->imageTag))['Id'];

        $updateCount = 0;

        foreach ($this->processes as $process) {
            try {
                $processImageId = $process->getImageId();

                if ($processImageId !== $imageId) {
                    $process->requestRestartToUpdate();
                    ++$updateCount;
                }
            }
            catch (Throwable $exception) {
                $this->logger->error(sprintf('Failed to get image id : %s', $exception->getMessage()), ['exception' => $exception]);
            }
        }

        if ($updateCount > 0) {
            $this->logger->info(sprintf('%d processes need an update', $updateCount));
        }
        else {
            $this->logger->info('Found no process to update');
        }
    }

    public function listRunningProcesses(): array
    {
        $this->processes = $this->listProcesses(false);
        return $this->processes;
    }

    /**
     * @param int[] $userIds
     * @return DockerBackendProcess[]
     * @throws Throwable
     */
    protected function listProcesses(bool $all, array $userIds = []): array
    {
        $processes = [];

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
                $processes[$userId] = new DockerBackendProcess($this->docker, $container['Id']);
            }
        }

        return $processes;
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

        $this->pullImageIfNotExists($this->imageTag);

        $mountSubpath = $userId . '/backend';

        if ($this->mountType === 'bind') {
            $mountSource = $this->mountSrc . '/' . $mountSubpath;
            $mountExtra = [];
        }
        elseif ($this->mountType === 'volume') {
            $mountSource = $this->mountSrc;
            $mountExtra = [
                'VolumeOptions' => [
                    'Subpath' => $mountSubpath,
                ]
            ];
        }
        else {
            throw new \RuntimeException('Unsupported mount type ' . $this->mountType);
        }

        $mount = [
            'Type' => $this->mountType,
            'Source' => $mountSource,
            'Target' => '/var/lib/athorrent-backend',
            'ReadOnly' => false,
            ...$mountExtra,
        ];

        $container = await($this->docker->containerCreate([
            'Image' => $this->imageTag,
            'Cmd' => ['--port', "$port"],
            'User' => 'www-data',
            'WorkingDir' => '/var/lib/athorrent-backend',
            'Healthcheck' => [
                'Test' => ["NONE"]
            ],
            'HostConfig' => [
                'NetworkMode' => 'host',
                'Mounts' => [$mount],
            ],
            'Labels' => [
                'com.athorrent.user' => "$userId",
            ]
        ], 'athorrentd_' . $userId));

        await($this->docker->containerStart($container['Id']));

        $this->processes[$userId] = new DockerBackendProcess($this->docker, $container['Id']);

        return $this->processes[$userId];
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
