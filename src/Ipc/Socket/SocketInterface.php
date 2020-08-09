<?php

namespace Athorrent\Ipc\Socket;

interface SocketInterface
{
    public function shutdown(): void;

    public function close(): void;
}
