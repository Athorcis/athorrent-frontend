<?php

namespace Athorrent\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader as BaseDirectoryLoader;

class AttributeDirectoryLoader extends BaseDirectoryLoader
{
    public function __construct(AttributeClassLoader $classLoader)
    {
        parent::__construct(new FileLocator(), $classLoader);
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === 'extra';
    }
}
