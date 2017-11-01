<?php

namespace Athorrent\Ipc\Socket;

interface SocketInterface
{
    public function shutdown();

    public function close();
}
