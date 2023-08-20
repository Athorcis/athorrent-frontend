<?php

namespace Athorrent\Ipc;

use RuntimeException;

class JsonService
{
    public function __construct(private readonly string $clientSocketType, private readonly string $address)
    {
    }

    public function call(string $action, $parameters = [])
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

            throw new RuntimeException($response->getData());
        }

        throw new RuntimeException('no response');
    }
}
