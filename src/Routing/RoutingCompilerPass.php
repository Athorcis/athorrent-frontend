<?php

namespace Athorrent\Routing;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RoutingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $router = $container->findDefinition('router.default');

        $router->setClass(Router::class);
        $options = $router->getArgument(2);
        $options['generator_dumper_class'] = PhpGeneratorDumper::class;
        $router->replaceArgument(2, $options);
    }
}
