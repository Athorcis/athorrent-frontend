<?php

namespace Athorrent\Backend;

use Athorrent\Database\Entity\User;
use Athorrent\Ipc\Exception\IpcException;
use Athorrent\Ipc\Exception\JsonServiceException;
use Athorrent\Ipc\Exception\SocketException;
use Athorrent\Utils\LegacyClient;

class LegacyBackend implements BackendInterface
{
    use BackendTrait;

    private readonly LegacyClient $client;

    public function __construct(User $user)
    {
        $this->client = new LegacyClient($user);
        $this->initBackend($user);
    }

    /**
     * @param string $action
     * @param array $parameters
     * @return mixed
     * @throws BackendUnavailableException
     */
    public function call(string $action, array $parameters = []): mixed
    {
        $state = $this->ensureRunningState();

        try {
            return $this->client->call($action, $parameters);
        }
        catch (IpcException $e) {
            throw new BackendUnavailableException($state, $e);
        }
    }

    /**
     * @return bool
     * @throws JsonServiceException
     * @throws SocketException
     */
    public function ping(): bool
    {
        try {
            return $this->client->call('ping') === 'pong';
        }
        catch (IpcException) {}

        return false;
    }

    public function clean(): void
    {
        $socketPath = $this->client->getEndpoint();

        if (file_exists($socketPath)) {
            unlink($socketPath);
        }

        // @TODO remove fastresume if needed
        // @TODO remove torrent data if needed
    }
}
