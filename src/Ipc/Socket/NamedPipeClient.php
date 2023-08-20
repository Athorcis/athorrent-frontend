<?php

namespace Athorrent\Ipc\Socket;

use Athorrent\Utils\ServiceUnavailableException;

class NamedPipeClient extends NamedPipe implements ClientSocketInterface
{
    public function __construct(string $path)
    {
        $this->namedPipe = @fopen($path, 'rb+');

        if (!$this->namedPipe) {
            throw new ServiceUnavailableException('SERVICE_NOT_RUNNING');
        }
    }

    public function read(string|false &$buffer, int $length): int
    {
        $buffer = fread($this->namedPipe, $length);

        if ($buffer === false) {
            $bytesRead = -1;
        } else {
            $bytesRead = strlen($buffer);
        }

        return $bytesRead;
    }

    public function write(string $buffer, int $length): int
    {
        $bytesWritten = fwrite($this->namedPipe, $buffer, $length);
        fflush($this->namedPipe);

        if ($bytesWritten === false) {
            $bytesWritten = -1;
        }

        return $bytesWritten;
    }
}
