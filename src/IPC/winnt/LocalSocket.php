<?php

namespace Athorrent\IPC;

abstract class LocalSocket_winnt implements SocketInterface
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
