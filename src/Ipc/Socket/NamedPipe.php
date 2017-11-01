<?php

namespace Athorrent\Ipc\Socket;

abstract class NamedPipe implements SocketInterface
{
    protected $namedPipe;

    public function shutdown()
    {
    }

    public function close()
    {
        fclose($this->namedPipe);
    }
}
