<?php

namespace Athorrent\IPC;

interface SocketInterface
{
    public function shutdown();

    public function close();
}
