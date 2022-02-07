<?php

namespace Athorrent\Ipc;

use RuntimeException;

class JsonService
{
    private string $clientSocketType;

    private string $address;

    public function __construct(string $clientSocketType, string $address)
    {
        $this->clientSocketType = $clientSocketType;
        $this->address = $address;
    }

    public function call($action, $parameters = array())
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
