<?php

namespace Athorrent\Backend\Process;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

readonly class BackendProcessManagerFactory
{
    public function __construct(
        #[AutowireLocator(BackendProcessManagerInterface::class, defaultIndexMethod: 'getType')]
        private ContainerInterface $serviceLocator
    ) {}

    public function get(string $type): BackendProcessManagerInterface
    {
        if (!$this->serviceLocator->has($type)) {
            throw new RuntimeException('unsupported type: ' . $type);
        }

        return $this->serviceLocator->get($type);
    }
}
