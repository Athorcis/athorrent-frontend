<?php

namespace Athorrent\IPC;

use Athorrent\Utils\ServiceUnavailableException;

class LocalClientSocket_linux extends LocalSocket_linux implements ClientSocketInterface
{
    public function __construct($path)
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

        if (!socket_connect($this->socket, $path)) {
            throw new ServiceUnavailableException('SERVICE_NOT_RUNNING');
        }
    }

    public function read(&$buffer, $length)
    {
        $bytesRead = socket_recv($this->socket, $buffer, $length, 0);

        if ($bytesRead === false) {
            $bytesRead = -1;
        }

        return $bytesRead;
    }

    public function write($buffer, $length)
    {
        $bytesWritten = socket_send($this->socket, $buffer, $length, 0);

        if ($bytesWritten === false) {
            $bytesWritten = -1;
        }

        return $bytesWritten;
    }
}
