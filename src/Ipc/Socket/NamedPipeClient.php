<?php

namespace Athorrent\Ipc\Socket;

use Athorrent\ErrorUtils;
use Athorrent\Ipc\Exception\SocketException;

class NamedPipeClient extends NamedPipe implements ClientSocketInterface
{
    /**
     * @throws SocketException
     */
    public function __construct(string $path)
    {
        $this->namedPipe = @fopen($path, 'rb+');

        if (!$this->namedPipe) {
            throw new SocketException(sprintf('fopen failed with error %s', ErrorUtils::getLastErrorMessage()));
        }
    }

    public function read(string|false|null &$buffer, int $length): int
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
        ErrorUtils::resetLastError();
        $bytesWritten = @fwrite($this->namedPipe, $buffer, $length);

        if ($bytesWritten === false) {
            $error = ErrorUtils::getLastError();

            if ($error) {
                throw new SocketException(sprintf('fwrite failed with error %s', $error['message']));
            }
        }

        fflush($this->namedPipe);

        if ($bytesWritten === false) {
            $bytesWritten = -1;
        }

        return $bytesWritten;
    }
}
