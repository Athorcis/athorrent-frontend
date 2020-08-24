<?php

namespace Athorrent\Utils\Search;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface TorrentSourceInterface
{
    public function getId(): string;

    public function getName(): string;

    /**
     * @param HttpClientInterface $http
     * @param string $query
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function sendRequest(HttpClientInterface $http, string $query): ResponseInterface;

    /**
     * @param ResponseInterface $response
     * @return TorrentInfo[]
     */
    public function parseResponse(ResponseInterface $response): array;
}
