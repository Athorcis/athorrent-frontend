<?php

namespace Athorrent\Ipc;

use RuntimeException;

class JsonService
{
    public function __construct(private string $clientSocketType, private string $address)
    {
    }

    public function call($action, $parameters = [])
    {
        $request = new JsonRequest($action, $parameters);

        $client = new JsonClient($this->clientSocketType, $this->address);

        $client->send($request);
        $response = $client->recv();

        $client->disconnect();

        if ($response) {
            if ($response->isSuccess()) {
                return $response->getData();
            }

            throw new RuntimeException($response->getData());
        }

        throw new RuntimeException('no response');
    }
}
