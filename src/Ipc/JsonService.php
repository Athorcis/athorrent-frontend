<?php

namespace Athorrent\Ipc;

use Athorrent\Ipc\Exception\JsonServiceException;
use Athorrent\Ipc\Exception\SocketException;
use RuntimeException;

class JsonService
{
    public function __construct(private readonly string $clientSocketType, private readonly string $address)
    {
    }

    /**
     * @throws JsonServiceException
     * @throws SocketException
     */
    public function call(string $action, array $parameters = []): mixed
    {
        $request = new JsonRequest($action, $parameters);

        $client = new JsonClient($this->clientSocketType, $this->address);

        $client->send($request);
        $response = $client->recv();

        $client->disconnect();

        if ($response instanceof JsonResponse) {
            if ($response->isSuccess()) {
                return $response->getData();
            }

            $error = $response->getData();

            if (is_string($error)) {
                $error = ['message' => $error];
            }

            $this->onError($error);
        }

        throw new JsonServiceException('no response');
    }

    /**
     * @param array{message: string, id: string|null} $error
     * @return void
     */
    protected function onError(array $error): void
    {
        throw new RuntimeException($error['message']);
    }
}
