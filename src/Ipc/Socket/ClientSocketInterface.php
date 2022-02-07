<?php

namespace Athorrent\Ipc\Socket;

interface ClientSocketInterface extends SocketInterface
{
    public function read(&$buffer, $length): int;

    public function write($buffer, $length): int;
}
