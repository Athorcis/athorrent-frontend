<?php

namespace Athorrent\IPC;

interface ClientSocketInterface extends SocketInterface
{
    public function read(&$buffer, $length);

    public function write($buffer, $length);
}
