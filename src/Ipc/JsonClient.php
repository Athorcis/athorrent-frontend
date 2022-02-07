<?php

namespace Athorrent\Ipc;

use Athorrent\Ipc\Socket\ClientSocketInterface;

class JsonClient
{
    private ClientSocketInterface $clientSocket;

    public function __construct(string $clientSocketType, string $address)
    {
        $this->clientSocket = new $clientSocketType($address);
    }

    public function disconnect(): void
    {
        $this->clientSocket->shutdown();
        $this->clientSocket->close();
    }

    public function recv(): ?JsonResponse
    {
        $rawResponse = '';

        do {
            $bytesRead = $this->clientSocket->read($buffer, 1024);

            if ($bytesRead > 0) {
                $rawResponse .= $buffer;
            } else {
                break;
            }
        } while ($rawResponse[strlen($rawResponse) - 1] !== '\n');

        if ($rawResponse) {
            return JsonResponse::parse($rawResponse);
        }

        return null;
    }

    public function send(JsonRequest $request): void
    {
        $rawRequest = $request->toRawRequest();
        $length = strlen($rawRequest);
        $offset = 0;

        while ($offset < $length) {
            $bytesWritten = $this->clientSocket->write(substr($rawRequest, $offset, 1024), min(1024, $length - $offset));

            if ($bytesWritten > 0) {
                $offset += $bytesWritten;
            } else {
                break;
            }
        }
    }
}
