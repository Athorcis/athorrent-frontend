<?php

namespace Athorrent\Backend;

use Athorrent\Backend\Process\BackendProcessFailedException;
use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Backend\Process\BackendProcessManagerFactory;
use Athorrent\Backend\Process\BackendProcessManagerInterface;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use SplObjectStorage;
use SplQueue;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Throwable;
use function React\Async\async;
use function React\Async\await;
use function React\Async\delay;
use function React\Async\parallel;
use function React\Promise\all;
use function React\Promise\resolve;
use function React\Promise\Timer\sleep;
use function React\Promise\Timer\timeout;

class BackendManager
{
    private BackendProcessManagerInterface $backendProcessManager;

    private PromiseInterface $runPromise;

    /** @var SplObjectStorage<PromiseInterface> */
    private SplObjectStorage $sleepPromises;

    /** @var array<int, BackendInterface>  */
    private array $backends;

    /** @var SplObjectStorage<BackendInterface> */
    private SplObjectStorage $failedBackends;

    /** @var SplQueue<BackendInterface>  */
    private SplQueue $startQueue;

    /** @var SplQueue<BackendInterface>  */
    private SplQueue $heartbeatQueue;

    /** @var SplQueue<BackendInterface>  */
    private SplQueue $nextHeartbeatQueue;

    private array $addingUser = [];
    private array $removingUser = [];

    private array $stopRequested = [];

    private bool $stopping = false;

    public function __construct(
        private readonly BackendProcessManagerFactory $factory,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepo,
        #[Target('backend_restart')]
        private readonly RateLimiterFactoryInterface $backendRestartLimiter,
        private readonly BackendFactory $backendFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->sleepPromises = new SplObjectStorage();
    }

    public function run(): void
    {
        $this->backendProcessManager = $this->factory->get('docker');

        $this->backends = $this->initializeBackends();
        $this->failedBackends = new SplObjectStorage();

        $this->startQueue = new SplQueue();
        $this->startQueue->setIteratorMode(SplQueue::IT_MODE_DELETE);

        $this->heartbeatQueue = new SplQueue();
        $this->nextHeartbeatQueue = new SplQueue();

        $this->enqueueCreatedBackends($this->backends);

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
            Loop::futureTick(async(function () use ($keepBackends, $resolve, $reject) {
                try {
                    $this->stopping = false;
                    $this->stop($keepBackends);
                    $resolve(null);
                }
                catch (Throwable $e) {
                    $reject($e);
                }
            }));
        });
    }

    protected function dottedSleep(float $time): bool
    {
        for ($i = 0; $i < $time; $i++) {
            if (!$this->sleep(1)) return false;

            if (count($this->stopRequested) > 0) {
                break;
            }
        }

        return true;
    }

    protected function sleep(float $time): bool
    {
        if ($this->stopping) {
            return false;
        }

        $promise = sleep($time);
        $this->sleepPromises->offsetSet($promise);

        try {
            await($promise);
        }
        catch (RuntimeException $e) {
            if ($e->getMessage() === 'Timer cancelled') {
                return false;
            }
        }
        finally {
            $this->sleepPromises->offsetUnset($promise);
        }

        return !$this->stopping;
    }

    protected function isStopRequested(BackendInterface $backend): bool
    {
        return $this->stopRequested[$backend->getUser()->getId()] ?? false;
    }

    protected function startProcess(BackendInterface $backend): void
    {
        if ($this->isStopRequested($backend)) {
            $backend->setState(BackendState::Stopping);
            return;
        }

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
            $this->failedBackends->offsetSet($backend);
            $this->cleanProcess($backend);
        }
    }

    protected function processStartQueue(): void
    {
        while (true) {
            foreach ($this->startQueue as $backend) {
                $this->startProcess($backend);

                if ($this->stopping) {
                    return;
                }

                if (!$this->sleep(5)) return;
            }

            if ($this->startQueue->isEmpty()) {
                if (!$this->dottedSleep(5)) return;
            }
        }
    }

    protected function processHeartbeat(BackendInterface $backend): void
    {
        if ($this->isStopRequested($backend)) {
            $backend->setState(BackendState::Stopping);
            return;
        }

        $this->logger->debug(sprintf('Sending heartbeat to %s...', $backend));

        try {
            $restart = !$backend->ping();

            if ($restart) {
                $this->logger->error(sprintf('Heartbeat to %s failed', $backend));
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

            if (!$this->dottedSleep(10)) return;
        }
    }

    /**
     * @return array<int, BackendInterface>
     */
    protected function initializeBackends(): array
    {
        $users = $this->userRepo->findAll();
        return $this->initializeBackendsForUsers($users);
    }

    /**
     * @param User[] $users
     * @return BackendInterface[]
     */
    protected function initializeBackendsForUsers(array $users, $keepAll = false): array
    {
        $backends = [];

        foreach ($users as $user) {
            try {
                $backend = $this->backendFactory->create($user);
            }
            catch (\Exception) {
                $this->logger->error(sprintf('Unknown client type: %s', $user->getClientType()));
                continue;
            }

            $backends[$user->getId()] = $backend;
        }

        $processes = $this->backendProcessManager->listRunningProcesses();

        foreach ($processes as $userId => $process) {
            if (isset($backends[$userId])) {
                $this->logger->info(sprintf('Found process for %s...', $backends[$userId]));
                $backends[$userId]->setProcess($process);
            }
            elseif (!$keepAll) {
                $process->stop();
            }
        }

        foreach ($backends as $backend) {
            if ($backend->getProcess() && $backend->ping()) {
                $backend->setState(BackendState::Running);
            }
            else {
                $backend->setState(BackendState::Starting);
            }
        }

        return $backends;
    }

    protected function enqueueCreatedBackends(array $backends): void
    {
        foreach ($backends as $backend) {
            if ($backend->getProcess() === null) {
                $this->startQueue->enqueue($backend);
            }
            else {
                $this->nextHeartbeatQueue->enqueue($backend);
            }
        }
    }

    public function addUser(int $id)
    {
        if (isset($this->backends[$id])) {
            throw new BadRequestHttpException(sprintf('A backend is already managed for user %d', $id));
        }

        if (isset($this->addingUser[$id])) {
            throw new BadRequestHttpException(sprintf('A backend for user %d is already being added', $id));
        }

        $this->addingUser[$id] = true;

        try {
            $this->logger->info("Adding backend $id...");
            $user = $this->userRepo->find($id);

            if ($user === null) {
                throw new NotFoundHttpException(sprintf('User %s does not exist', $id));
            }

            $backends = $this->initializeBackendsForUsers([$user], true);

            $this->backends[$id] = $backend = $backends[$id];
            $this->enqueueCreatedBackends($backends);

            $this->waitForState($backend, [BackendState::Running, BackendState::Failed]);

            if ($backend->getState() === BackendState::Failed) {
                throw new \Exception('failed to start backend');
            }
        }
        finally {
            unset($this->addingUser[$id]);
        }
    }

    /**
     * @param BackendState[] $states
     * @return void
     */
    protected function waitForState(BackendInterface $backend, array $states): void
    {
        while (!in_array($backend->getState(), $states, true)) {
            if (!$this->sleep(1)) {
                return;
            }
        }
    }

    public function removeUser(int $id)
    {
        if (!isset($this->backends[$id])) {
            throw new NotFoundHttpException(sprintf('No backend found for user %d', $id));
        }

        if (isset($this->removingUser[$id])) {
            throw new BadRequestHttpException(sprintf('Backend for user %d is already being removed', $id));
        }

        $this->removingUser[$id] = true;

        try {
            $this->logger->info("Removing backend $id...");
            $backend = $this->initiateBackendStop($id);

            $this->cleanProcess($backend);
            $this->finalizeBackendStop($id, $backend);
        } finally {
            unset($this->removingUser[$id]);
        }
    }

    protected function initiateBackendStop(int $id): BackendInterface
    {
        $backend = $this->backends[$id];
        $state = $backend->getState();

        if (in_array($state, [BackendState::Starting, BackendState::Updating, BackendState::Running, BackendState::Unknown], true)) {
            $this->stopRequested[$id] = true;
            $this->waitForState($backend, [BackendState::Stopping]);
        } elseif ($state === BackendState::Failed) {
            $this->failedBackends->offsetUnset($backend);
        }

        return $backend;
    }

    public function finalizeBackendStop(int $id, BackendInterface $backend): void
    {
        unset($this->backends[$id], $this->stopRequested[$id]);

        $user = $backend->getUser();
        $this->backendFactory->remove($user);
        $this->entityManager->detach($user);
        $this->resetRestartLimiter($id);
    }

    public function detachUser(int $id)
    {
        if (!isset($this->backends[$id])) {
            throw new NotFoundHttpException(sprintf('No backend found for user %d', $id));
        }

        if (isset($this->removingUser[$id])) {
            throw new BadRequestHttpException(sprintf('Backend for user %d is already being detached', $id));
        }

        $this->removingUser[$id] = true;

        try {
            $this->logger->info("Detaching backend $id...");
            $backend = $this->initiateBackendStop($id);

            $this->detachProcess($backend);
            $this->finalizeBackendStop($id, $backend);
        } finally {
            unset($this->removingUser[$id]);
        }
    }

    protected function resetRestartLimiter(int $id): void
    {
        $this->backendRestartLimiter->create($id)->reset();
    }

    public function clear()
    {
        $this->logger->info('Clearing backends...');
        $promises = [];

        foreach ($this->backends as $id => $backend) {
            $promises[] = async(function () use ($id) {
                try {
                    $this->removeUser($id);
                }
                catch (Throwable $e) {
                    dump($e);
                }
            })();
        }

        await(all($promises));
    }

    protected function cleanProcess(BackendInterface $backend): void
    {
        $this->backendProcessManager->clean($backend->getUser());

        $backend->clean();
    }

    protected function detachProcess(BackendInterface $backend): void
    {
        $this->backendProcessManager->detach($backend->getUser());
    }

    /**
     * @throws BackendProcessFailedException
     */
    protected function createProcess(BackendInterface $backend): BackendProcessInterface
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
            if ($backend->ping()) {
                break;
            }

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

    public function getBackendCount(): int
    {
        return count($this->backends);
    }

    public function getFailedBackendsCount(): int
    {
        return $this->failedBackends->count();
    }
}
