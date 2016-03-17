<?php

namespace Athorrent\IPC;

abstract class LocalSocket_linux implements SocketInterface
{
    protected $socket;

    public function shutdown()
    {
        socket_shutdown($this->socket, 2);
    }

    public function close()
    {
        socket_close($this->socket);
    }
}
