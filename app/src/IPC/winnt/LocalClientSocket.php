<?php

namespace Athorrent\IPC;

use Athorrent\Utils\ServiceUnavailableException;

class LocalClientSocket_winnt extends LocalSocket_winnt implements ClientSocketInterface {
    public function __construct($path) {
        $this->namedPipe = @fopen($path, 'r+');

        if (!$this->namedPipe) {
            throw new ServiceUnavailableException('SERVICE_NOT_RUNNING');
        }
    }

    public function read(&$buffer, $length) {
        $buffer = fread($this->namedPipe, $length);

        if ($buffer === false) {
            $bytesRead = -1;
        } else {
            $bytesRead = strlen($buffer);
        }

        return $bytesRead;
    }

    public function write($buffer, $length) {
        $bytesWritten = fwrite($this->namedPipe, $buffer, $length);
        fflush($this->namedPipe);

        if ($bytesWritten === false) {
            $bytesWritten = -1;
        }

        return $bytesWritten;
    }
}

?>
