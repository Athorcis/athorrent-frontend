<?php

namespace Athorrent\Routing;

use Athorrent\View\View;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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

        $app['request_matcher_cache'] = new RequestMatcherCache($app);
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($app) {
            $attributes = $event->getRequest()->attributes;

            $app['request_context']->setParameter('_prefixId', $attributes->get('_prefixId'));

            foreach ($attributes->get('_route_params') as $key => $value) {
                if ($key[0] !== '_') {
                    $app['request_context']->setParameter($key, $value);
                }
            }
        });

        $dispatcher->addListener(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $event) use ($app) {
            $result = $event->getControllerResult();

            if ($result === null) {
                return ;
            }

            $request = $event->getRequest();

            if ($result instanceof View && !$request->attributes->get('_ajax')) {
                $result->setJsVar('routes', $app['ajax_route_descriptors']);
            }
        }, Application::EARLY_EVENT);
    }
}
