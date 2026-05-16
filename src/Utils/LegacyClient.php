<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Athorrent\Ipc\JsonService;
use Athorrent\Ipc\Socket\NamedPipeClient;
use Athorrent\Ipc\Socket\UnixSocketClient;
use Athorrent\UserVisibleException;

class LegacyClient extends JsonService
{
    private readonly string $endpoint;

    public function __construct(User $user)
    {
        $clientSocketClass = DIRECTORY_SEPARATOR === '\\' ? NamedPipeClient::class : UnixSocketClient::class;
        $endpoint = self::getEndpointFromUser($user);

        parent::__construct($clientSocketClass, $endpoint);

        $this->endpoint = $endpoint;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
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
