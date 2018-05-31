<?php

namespace Athorrent\Cache;

use Phpfastcache\Bundle\Twig\CacheExtension\Extension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $twigCacheExtension = $container->findDefinition(Extension::class);
        $twigCacheExtension->replaceArgument(0, $container->findDefinition('twig.cache.strategy'));
    }
}
