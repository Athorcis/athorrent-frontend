<?php

namespace Athorrent\Ipc;

use Athorrent\Ipc\Exception\JsonServiceException;
use Athorrent\Ipc\Exception\SocketException;
use Athorrent\Ipc\Socket\ClientSocketInterface;
use JsonException;

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

    /**
     * @throws JsonServiceException
     */
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

        if ($rawResponse !== '') {
            try {
                return JsonResponse::parse($rawResponse);
            }
            catch (JsonException $e) {
                throw new JsonServiceException(sprintf('failed to parse response : %s', $e->getMessage()), 0, $e);
            }
        }

        return null;
    }

    /**
     * @throws JsonServiceException
     * @throws SocketException
     */
    public function send(JsonRequest $request): void
    {
        try {
            $rawRequest = $request->toRawRequest();
        }
        catch (JsonException $e) {
            throw new JsonServiceException(sprintf('failed to encode request : %s', $e->getMessage()), 0, $e);
        }

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
