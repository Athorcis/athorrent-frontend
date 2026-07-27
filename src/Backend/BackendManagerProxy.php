<?php

declare(strict_types=1);

namespace Athorrent\Backend;

use Athorrent\Database\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BackendManagerProxy
{
    private bool $allowTransportExceptions = false;

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
    )
    {

    }

    public function setAllowTransportExceptions(bool $allowTransportExceptions): void
    {
        $this->allowTransportExceptions = $allowTransportExceptions;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function request(string $method, string $path, array $options = []): void
    {
        try {
            $response = $this->http->request($method, sprintf('http://%s:8080', $_ENV['BACKEND_MANAGER_HOST']) . $path, $options);
            $response->getHeaders(); // response is lazy so to get errors we need to access it
        }
        catch (TransportExceptionInterface $e) {
            if ($this->allowTransportExceptions) {
                $this->logger->notice(sprintf('Failed to connect to backend manager : %s', $e->getMessage()));
                return;
            }

            throw $e;
        }
    }

    public function addUser(User $user): void
    {
        $this->request('POST', '/user/add', ['query' => ['id' => $user->getId()]]);
    }

    public function removeUser(User $user): void
    {
        $this->request('DELETE', '/user/remove', ['query' => ['id' => $user->getId()]]);
    }

    public function detachUser(User $user): void
    {
        $this->request('DELETE', '/user/detach', ['query' => ['id' => $user->getId()]]);
    }

    public function clear(): void
    {
        $this->request('DELETE', '/clear');
    }
}
