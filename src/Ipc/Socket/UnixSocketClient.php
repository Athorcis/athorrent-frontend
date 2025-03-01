<?php

namespace Athorrent\Ipc\Socket;

use Athorrent\Ipc\Exception\SocketException;

class UnixSocketClient extends UnixSocket implements ClientSocketInterface
{

    /**
     * @param string $path
     * @throws SocketException
     */
    public function __construct(string $path)
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

        if ($this->socket === false) {
            throw new SocketException(sprintf("socket_create failed with error: %s", socket_strerror(socket_last_error())));
        }

        if (@!socket_connect($this->socket, $path)) {
            throw new SocketException(sprintf('socket_connect failed with error: %s', socket_strerror(socket_last_error())));
        }
    }

    public function read(string|false|null &$buffer, int $length): int
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
