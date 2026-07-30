<?php

declare(strict_types=1);

namespace Athorrent\Backend\Process\Docker;

use Clue\React\Docker\Client;
use Clue\React\Docker\Io\ResponseParser;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use Rize\UriTemplate;

/**
 * Extends clue/docker-react with container list filters (label/name/etc.).
 */
class DockerClient extends Client
{
    /**
     * @param array<string, list<string>> $filters
     * @return PromiseInterface<array<mixed>>
     */
    public function containerList($all = false, $size = false, array $filters = []): PromiseInterface
    {
        /** @var Browser $browser */
        $browser = $this->accessPrivate('browser');
        /** @var UriTemplate $uri */
        $uri = $this->accessPrivate('uri');
        /** @var ResponseParser $parser */
        $parser = $this->accessPrivate('parser');

        return $browser->get(
            $uri->expand(
                'containers/json{?all,size,filters}',
                [
                    'all' => $all ? 1 : null,
                    'size' => $size ? 1 : null,
                    'filters' => $filters !== [] ? json_encode($filters, JSON_THROW_ON_ERROR) : null,
                ]
            )
        )->then([$parser, 'expectJson']);
    }

    private function accessPrivate(string $property): mixed
    {
        return \Closure::bind(fn () => $this->{$property}, $this, Client::class)();
    }
}
