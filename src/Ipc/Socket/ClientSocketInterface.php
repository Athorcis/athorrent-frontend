<?php

namespace Athorrent\Ipc\Socket;

interface ClientSocketInterface extends SocketInterface
{
    public function read(string|false|null &$buffer, int $length): int;

    public function write(string $buffer, int $length): int;
}
