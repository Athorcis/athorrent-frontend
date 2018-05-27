<?php

namespace Athorrent\Cache;


use Athorrent\Cache\Twig\GenerationalCacheStrategy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $twigCacheExtension = $container->findDefinition('Phpfastcache\Bundle\Twig\CacheExtension\Extension');

        $twigCacheExtension->replaceArgument(0, GenerationalCacheStrategy::class);
    }
}
