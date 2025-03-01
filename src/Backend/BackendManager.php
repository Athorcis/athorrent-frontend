<?php

namespace Athorrent\Backend;

use Athorrent\Backend\Process\BackendProcessFailedException;
use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Backend\Process\BackendProcessManagerFactory;
use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Ipc\Exception\IpcException;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use SplObjectStorage;
use SplQueue;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Throwable;
use function React\Async\async;
use function React\Async\await;
use function React\Async\delay;
use function React\Async\parallel;
use function React\Promise\resolve;
use function React\Promise\Timer\sleep;
use function React\Promise\Timer\timeout;

class BackendManager
{
    private BackendProcessManagerInterface $backendProcessManager;

    private PromiseInterface $runPromise;

    /** @var SplObjectStorage<PromiseInterface> */
    private SplObjectStorage $sleepPromises;

    /** @var array<int, Backend>  */
    private array $backends;

    /** @var SplQueue<Backend>  */
    private SplQueue $startQueue;

    /** @var SplQueue<Backend>  */
    private SplQueue $heartbeatQueue;

    /** @var SplQueue<Backend>  */
    private SplQueue $nextHeartbeatQueue;

    private bool $stopping = false;

    public function __construct(
        private readonly BackendProcessManagerFactory $factory,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepo,
        private readonly RateLimiterFactory $backendRestartLimiter,
    ) {
        $this->sleepPromises = new SplObjectStorage();
    }

    public function run(): void
    {
        $this->backendProcessManager = $this->factory->get($_ENV['BACKEND_TYPE'] ?? 'foreground');

        $this->backends = $this->initializeBackends();

        $this->startQueue = new SplQueue();
        $this->startQueue->setIteratorMode(SplQueue::IT_MODE_DELETE);

        $this->heartbeatQueue = new SplQueue();
        $this->nextHeartbeatQueue = new SplQueue();

        foreach ($this->backends as $backend) {
            if ($backend->getProcess() === null) {
                $this->startQueue->enqueue($backend);
            }
            else {
                $this->nextHeartbeatQueue->enqueue($backend);
            }
        }

        $this->runPromise = parallel([
            async($this->processHeartbeatQueue(...)),
            async($this->processStartQueue(...)),
        ]);

        Loop::run();
    }

    public function update(): void
    {
        if (!$this->backendProcessManager->supportsUpdate()) {
            $this->logger->warning('Backend manager does not support update');
            return;
        }

        $this->backendProcessManager->requestUpdate();
    }

    public function stop(bool $keepBackends = true): void
    {
        if ($this->stopping) {
            return;
        }

        $this->stopping = true;

        foreach ($this->sleepPromises as $promise) {
            $promise->cancel();
        }

        $this->sleepPromises = new SplObjectStorage();

        try {
            await($this->runPromise);
        }
        catch (Throwable $e) {
            $this->logger->error(sprintf('Backend manager failed with error : %s', $e->getMessage()), ['exception' => $e]);
        }

        if (!$keepBackends || !$this->backendProcessManager->isPersistent()) {
            foreach ($this->backends as $backend) {
                $state = $backend->getState();

                if ($state === BackendState::Running) {
                    $this->logger->info(sprintf('Stopping %s...', $backend));

                    $this->cleanProcess($backend);
                    $backend->setState(BackendState::Stopped);
                }
                elseif ($state !== BackendState::Failed) {
                    $this->logger->info(sprintf('Cleaning up %s...', $backend));
                    $this->cleanProcess($backend);
                }
            }
        }

        $this->backends = [];
        $this->startQueue = new SplQueue();
        $this->heartbeatQueue = new SplQueue();

        $this->stopping = false;
    }

    public function stopAsync(bool $keepBackends = true): PromiseInterface
    {
        if ($this->stopping) {
            return resolve(null);
        }

        $this->stopping = true;

        return new Promise(function ($resolve, $reject) use ($keepBackends) {
            Loop::futureTick(function () use ($keepBackends, $resolve, $reject) {
                try {
                    $this->stopping = false;
                    $this->stop($keepBackends);
                    $resolve(null);
                }
                catch (Throwable $e) {
                    $reject($e);
                }
            });
        });
    }

    protected function sleep(float $time): bool
    {
        if ($this->stopping) {
            return false;
        }

        $promise = sleep($time);
        $this->sleepPromises->attach($promise);

        try {
            await($promise);
        }
        catch (RuntimeException $e) {
            if ($e->getMessage() === 'Timer cancelled') {
                return false;
            }
        }
        finally {
            $this->sleepPromises->detach($promise);
        }

        return !$this->stopping;
    }

    protected function processStart(Backend $backend): void
    {
        $user = $backend->getUser();

        $limiter = $this->backendRestartLimiter->create($user->getId());

        if ($limiter->consume()->isAccepted()) {
            try {
                $backend->setProcess($this->createProcess($backend));
                $this->heartbeatQueue->enqueue($backend);

            } catch (Throwable $exception) {
                $this->logger->error(sprintf('Failed to start %s : %s', $backend, $exception->getMessage()), ['exception' => $exception]);
                $this->startQueue->enqueue($backend);
            }
        }
        else {
            $this->logger->error(sprintf("Failed to start %s too many times", $backend));
            $backend->setState(BackendState::Failed);
            $this->cleanProcess($backend);
        }
    }

    protected function processStartQueue(): void
    {
        while (true) {
            foreach ($this->startQueue as $backend) {
                $this->processStart($backend);

                if ($this->stopping) {
                    return;
                }

                if (!$this->sleep(5)) return;
            }

            if ($this->startQueue->isEmpty()) {
                if (!$this->sleep(5)) return;
            }
        }
    }

    protected function processHeartbeat(Backend $backend): void
    {
        $this->logger->debug(sprintf('Sending heartbeat to %s...', $backend));
        try {
            $pingResult = $backend->ping();
            $restart = $pingResult !== 'pong';

            if ($restart) {
                $this->logger->error(sprintf('Heartbeat to %s returned an unexpected value %s', $backend, $pingResult));
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Fail to send heartbeat to %s : %s', $backend, $e->getMessage()), ['exception' => $e]);
            $restart = true;
        }

        if ($restart) {
            $backend->setState(BackendState::Starting);
            $this->startQueue->enqueue($backend);
        }
        elseif ($backend->getProcess()->shouldRestartToUpdate()) {
            $this->logger->info(sprintf('Restarting %s to update...', $backend));

            $backend->setState(BackendState::Updating);
            $this->startQueue->enqueue($backend);
        }
        else {
            $this->nextHeartbeatQueue->enqueue($backend);
        }
    }

    protected function rotateHeartbeatQueues(): void
    {
        foreach ($this->heartbeatQueue as $backend) {
            $this->nextHeartbeatQueue->enqueue($backend);
        }

        $this->heartbeatQueue = $this->nextHeartbeatQueue;
        $this->heartbeatQueue->setIteratorMode(SplQueue::IT_MODE_DELETE);

        $this->nextHeartbeatQueue = new SplQueue();
    }

    protected function processHeartbeatQueue(): void
    {
        while (true) {
            $this->rotateHeartbeatQueues();

            foreach ($this->heartbeatQueue as $backend) {
                $this->processHeartbeat($backend);

                if ($this->stopping) {
                    return;
                }
            }

            if (!$this->sleep(10)) return;
        }
    }

    /**
     * @return array<int, Backend>
     */
    protected function initializeBackends(): array
    {
        $backends = [];
        $users = $this->userRepo->findAll();

        foreach ($users as $user) {
            $backends[$user->getId()] = new Backend($user);
        }

        $processes = $this->backendProcessManager->listRunningProcesses();

        foreach ($processes as $userId => $process) {
            if (isset($backends[$userId])) {
                $this->logger->info(sprintf('Found process for %s...', $backends[$userId]));
                $backends[$userId]->setProcess($process);
            }
            else {
                $process->stop();
            }
        }

        foreach ($backends as $backend) {
            $backend->setState($backend->getProcess() ? BackendState::Running : BackendState::Starting);
        }

        return $backends;
    }

    protected function cleanProcess(Backend $backend): void
    {
        $this->backendProcessManager->clean($backend->getUser());

        $socketPath = $backend->getEndpoint();

        if (file_exists($socketPath)) {
            unlink($socketPath);
        }

        // @TODO remove fastresume if needed
        // @TODO remove torrent data if needed
    }

    /**
     * @throws BackendProcessFailedException
     */
    protected function createProcess(Backend $backend): BackendProcessInterface
    {
        $this->logger->info(sprintf("Cleaning up %s...", $backend));
        $this->cleanProcess($backend);

        $this->logger->info(sprintf("Starting %s...", $backend));

        try {
            $process = await(timeout(async(fn() => $this->backendProcessManager->create($backend->getUser()))(), 30));
        }
        catch (Throwable $e) {
            $this->cleanProcess($backend);
            throw $e;
        }

        $startTs = microtime(true);

        while (true) {
            try {
                if ($backend->ping() === 'pong') {
                    break;
                }
            }
            catch (IpcException) {}

            if (!$process->isRunning()) {
                throw new BackendProcessFailedException('process failed while starting', $backend, $process->getErrorInfo());
            }

            if (microtime(true) - $startTs > 10) {
                $process->stop();
                throw new BackendProcessFailedException('process start timeout expired', $backend, $process->getErrorInfo());
            }

            delay(0.5);
        }

        $this->logger->info(sprintf("Started %s", $backend));

        return $process;
    }
}
