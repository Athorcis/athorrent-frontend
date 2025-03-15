<?php

namespace Athorrent\Backend;

use Athorrent\Backend\Process\BackendProcessInterface;
use Athorrent\Database\Entity\User;
use Athorrent\Ipc\Exception\IpcException;
use Athorrent\Ipc\Exception\JsonServiceException;
use Athorrent\Ipc\Exception\SocketException;
use Athorrent\Ipc\JsonService;
use Athorrent\Ipc\Socket\NamedPipeClient;
use Athorrent\Ipc\Socket\UnixSocketClient;
use Athorrent\UserVisibleException;
use Symfony\Component\Filesystem\Filesystem;

class Backend extends JsonService
{
    private readonly string $statePath;

    private BackendState $state;

    private readonly string $endpoint;

    private ?BackendProcessInterface $process = null;

    public function __construct(private readonly User $user)
    {
        $clientSocketClass = DIRECTORY_SEPARATOR === '\\' ? NamedPipeClient::class : UnixSocketClient::class;
        $endpoint = self::getEndpointFromUser($user);

        parent::__construct($clientSocketClass, $endpoint);

        $this->statePath = $user->getBackendPath('state.txt');
        $this->state = $this->readState();
        $this->endpoint = $endpoint;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getState(): BackendState
    {
        return $this->state;
    }

    protected function readState(): BackendState
    {
        $state = @file_get_contents($this->statePath);
        return BackendState::tryFrom($state) ?? BackendState::Unknown;
    }

    public function setState(BackendState $state): void
    {
        if ($this->state !== $state) {
            $this->state = $state;

            $fs = new Filesystem();
            $fs->dumpFile($this->statePath, $state->value);
        }
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getProcess(): ?BackendProcessInterface
    {
        return $this->process;
    }

    public function setProcess(?BackendProcessInterface $process): void
    {
        $this->process = $process;
        $this->setState(BackendState::Running);
    }

    public function __toString(): string
    {
        return sprintf("backend[%d]", $this->user->getId());
    }

    /**
     * @param string $action
     * @param array $parameters
     * @return mixed
     * @throws BackendUnavailableException
     */
    public function callGuarded(string $action, array $parameters = []): mixed
    {
        $state = $this->getState();

        if (!in_array($state, [BackendState::Running, BackendState::Unknown], true)) {
            throw new BackendUnavailableException($state);
        }

        try {
            return parent::call($action, $parameters);
        }
        catch (IpcException $e) {
            throw new BackendUnavailableException($state, $e);
        }
    }

    /**
     * @return string|null
     * @throws JsonServiceException
     * @throws SocketException
     */
    public function ping(): ?string
    {
        return $this->call('ping');
    }

    protected function onError(array $error): void
    {
        if (isset($error['id'])) {
            switch ($error['id']) {
                case 'INVALID_MAGNET_URI':
                    throw new UserVisibleException('error.invalidMagnetUri');

                case 'INVALID_TORRENT_FILE':
                    throw new UserVisibleException('error.invalidTorrentFile');
            }
        }

        parent::onError($error);
    }

    public static function getEndpointFromUser(User $user): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return '\\\\.\\pipe\\athorrentd\\sockets\\' . $user->getPort() . '.sck';
        }

        return $user->getBackendPath('athorrentd.sck');
    }
}
