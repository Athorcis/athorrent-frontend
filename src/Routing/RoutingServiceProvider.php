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
        $app['url_generator'] = function (Application $app) {
            return new UrlGenerator($app['default_locale'], $app['routes'], $app['request_context']);
        };

        $app['controllers_factory'] = $app->factory(function (Application $app) {
            return new ControllerCollection($app['route_factory'], $app['default_locale'], $app['locales']);
        });

        $app['request_matcher_cache'] = new RequestMatcherCache($app['request_context']);
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber(new RoutingListener($app['request_context'], function () use ($app) {
            return $app['ajax_route_descriptors'];
        }));
    }
}
