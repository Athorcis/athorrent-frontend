<?php

namespace Athorrent\Ipc\Socket;

use Athorrent\Utils\ServiceUnavailableException;

class UnixSocketClient extends UnixSocket implements ClientSocketInterface
{
    public function __construct(string $path)
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

        if (!socket_connect($this->socket, $path)) {
            throw new ServiceUnavailableException('SERVICE_NOT_RUNNING');
        }
    }

    public function read(string|false &$buffer, int $length): int
    {
        $bytesRead = socket_recv($this->socket, $buffer, $length, 0);

        if ($bytesRead === false) {
            $bytesRead = -1;
        }

        return $bytesRead;
    }

    public function write(string $buffer, int $length): int
    {
        $bytesWritten = socket_send($this->socket, $buffer, $length, 0);

        if ($bytesWritten === false) {
            $bytesWritten = -1;
        }

        return $bytesWritten;
    }
}
