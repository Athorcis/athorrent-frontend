<?php

namespace Athorrent\Routing;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RoutingServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['action_map'] = function (Application $app) {
            return new ActionMap($app['routes']);
        };

        $app['url_generator'] = function (Application $app) {
            return new UrlGenerator($app['default_locale'], $app['action_map'], $app['routes'], $app['request_context']);
        };

        $app['request_matcher_cache'] = new RequestMatcherCache($app['request_context']);
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber(new RoutingListener($app['cache'], $app['request_context']));
    }
}
