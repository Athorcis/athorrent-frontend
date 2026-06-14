<?php

namespace Athorrent\Backend\Process\Docker;

use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use Athorrent\Security\UserManager;
use Clue\React\Docker\Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\ResponseException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;
use function React\Async\await;

#[AsTaggedItem('docker')]
class DockerBackendProcessManager implements BackendProcessManagerInterface
{
    /** @var array<int, DockerBackendProcess>  */
    private array $processes = [];

    public function __construct(
        private readonly Client $docker,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(BACKEND_DOCKER_QBITTORRENT_IMAGE)%')]
        private readonly string $qbittorrentImageTag,
        #[Autowire('%env(BACKEND_DOCKER_MOUNT_TYPE)%')]
        private readonly string $mountType,
        #[Autowire('%env(BACKEND_DOCKER_MOUNT_SRC)%')]
        private readonly string $mountSrc,
        #[Autowire('%env(BACKEND_DOCKER_NETWORK)%')]
        private readonly string $network,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserManager $userManager,
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
        $this->pullImage($this->qbittorrentImageTag);

        $qbittorrentImageId = await($this->docker->imageInspect($this->qbittorrentImageTag))['Id'];

        $updateCount = 0;

        foreach ($this->processes as $process) {
            try {
                $requestRestart = false;
                $processImageId = $process->getImageId();

                if ($processImageId !== $qbittorrentImageId) {
                    $requestRestart = true;
                }

                if ($requestRestart) {
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

    protected function createContainer(User $user, array $config): DockerBackendProcess
    {
        $this->pullImageIfNotExists($config['Image']);

        $userId = $user->getId();
        $config['Labels']['com.athorrent.user'] = "$userId";

        $container = await($this->docker->containerCreate($config, 'athorrentd_' . $userId));

        await($this->docker->containerStart($container['Id']));

        return new DockerBackendProcess($this->docker, $container['Id']);
    }

    protected function getMountConfig($source, $target, $readOnly = true): array
    {
        if ($this->mountType === 'bind') {
            $mountSource = $this->mountSrc . '/' . $source;
            $mountExtra = [];
        }
        elseif ($this->mountType === 'volume') {
            $mountSource = $this->mountSrc;
            $mountExtra = [
                'VolumeOptions' => [
                    'Subpath' => $source,
                ]
            ];
        }
        else {
            throw new RuntimeException('Unsupported mount type ' . $this->mountType);
        }

        return [
            'Type' => $this->mountType,
            'Source' => $mountSource,
            'Target' => $target,
            'ReadOnly' => $readOnly,
            ...$mountExtra,
        ];
    }

    protected function createQBittorrent(User $user): DockerBackendProcess
    {
        $userId = $user->getId();
        $port = $user->getPort();

        $this->userManager->initUserDirs($user);

        return $this->createContainer($user, [
            'Image' => $this->qbittorrentImageTag,
            'Cmd' => ["--torrenting-port=$port"],
            'User' => 'www-data',
            'HostConfig' => [
                'NetworkMode' => $this->network,
                'PortBindings' => [
                    "$port/tcp" => [[
                        "HostPort" => "$port",
                    ]],
                    "$port/udp" => [[
                        "HostPort" => "$port",
                    ]]
                ],
                'Mounts' => [
                    $this->getMountConfig(
                        $userId . '/backend/qbittorrent',
                        '/config',
                        false
                    ),
                    $this->getMountConfig(
                        $userId . '/backend/files',
                        '/downloads',
                        false,
                    ),
                ],
            ],
        ]);
    }

    public function create(User $user): DockerBackendProcess
    {
        $process = $this->createQBittorrent($user);
        $clientIp = $process->getClientIp();

        if (!empty($clientIp)) {
            sleep(1);
            $user->setClientIp($clientIp);
            $this->entityManager->flush();
        }

        $this->processes[$user->getId()] = $process;

        return $process;
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

    public function detach(User $user): void
    {
        $userId = $user->getId();

        if (isset($this->processes[$userId])) {
            unset($this->processes[$userId]);
        }
    }
}
