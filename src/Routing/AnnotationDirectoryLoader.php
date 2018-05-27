<?php

namespace Athorrent\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader as BaseDirectoryLoader;

class AnnotationDirectoryLoader extends BaseDirectoryLoader
{
    public function __construct(AnnotationClassLoader $classLoader)
    {
        parent::__construct(new FileLocator(), $classLoader);
    }

    public function supports($resource, $type = null)
    {
        return $type === 'extra';
    }
}
