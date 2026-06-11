<?php

namespace Athorrent\Backend;

use Athorrent\Database\Entity\User;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class BackendManagerProxy
{
    public function __construct(private HttpClientInterface $http)
    {

    }

    protected function request(string $method, string $path, array $options = [])
    {
        try {
            $this->http->request($method, 'http://127.0.0.1:8080' . $path, $options);
        }
        catch (TransportExceptionInterface $e) {
            if (str_starts_with($e->getMessage(), 'Failed to connect to')) {
                return;
            }

            throw $e;
        }
        catch (ServerExceptionInterface $e) {
            dump($e->getResponse()->getContent(false));
        }
        catch (Throwable $e) {
            dump($e);
            throw $e;
        }
    }

    public function addUser(User $user)
    {
        $this->request('POST', '/user/add', ['query' => ['id' => $user->getId()]]);
    }

    public function removeUser(User $user)
    {
        $this->request('DELETE', '/user/remove', ['query' => ['id' => $user->getId()]]);
    }

    public function clear()
    {
        $this->request('DELETE', '/clear');
    }
}
