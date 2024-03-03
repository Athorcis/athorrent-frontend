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

            $error = $response->getData();

            if (is_string($error)) {
                $error = ['message' => $error];
            }

            $this->onError($error);
        }

        throw new RuntimeException('no response');
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
