<?php

namespace Athorrent\Ipc\Socket;

abstract class NamedPipe implements SocketInterface
{
    /** @var resource */
    protected $namedPipe;

    public function shutdown(): void
    {
    }

    public function close(): void
    {
        fclose($this->namedPipe);
    }
}
