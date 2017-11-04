<?php

namespace Athorrent\Security\Csrf;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CsrfServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['csrf.listener'] = function () {
            return new CsrfListener();
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['csrf.listener']);
    }
}
