<?php

namespace Athorrent\Ipc\Socket;

use Athorrent\Ipc\Exception\SocketException;

interface ClientSocketInterface extends SocketInterface
{
    public function read(string|false|null &$buffer, int $length): int;

    /**
     * @throws SocketException
     */
    public function write(string $buffer, int $length): int;
}
