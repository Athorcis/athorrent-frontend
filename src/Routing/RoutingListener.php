<?php

namespace Athorrent\Routing;

use Athorrent\View\View;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

class RoutingListener implements EventSubscriberInterface
{
    private $cache;

    private $requestContext;

    private $routeDescriptors;

    public function __construct(CacheInterface $cache, RequestContext $requestContext)
    {
        $this->cache = $cache;
        $this->requestContext = $requestContext;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $this->initAjaxRouteDescriptors($request);

        $attributes = $request->attributes;

        $this->requestContext->setParameter('_prefixId', $attributes->get('_prefixId'));

        foreach ($attributes->get('_route_params') as $key => $value) {
            if ($key[0] !== '_') {
                $this->requestContext->setParameter($key, $value);
            }
        }
    }

    protected function initAjaxRouteDescriptors(Request $request)
    {
        $locale = $request->getLocale();
        $ajaxRouteDescriptorsKey = 'ajax_route_descriptors_' . $locale;

        if ($this->cache->has($ajaxRouteDescriptorsKey)) {
            $ajaxRouteDescriptors = $this->cache->get($ajaxRouteDescriptorsKey);
        } else {
            $ajaxRouteDescriptors = [];

            foreach ($this->cache->get('routes') as $route) {
                if ($route->hasDefault('_action')) {
                    $action = $route->getDefault('_action');
                    $prefixId = $route->getDefault('_prefixId');

                    if ($route->getDefault('_ajax')) {
                        if ($locale === 'fr') {
                            if ($route->getDefault('_locale')) {
                                $ajaxRouteDescriptors[$action][$prefixId] = [
                                    $route->getMethods()[0],
                                    $route->getPath()
                                ];
                            }
                        } else {
                            if (!$route->getDefault('_locale')) {
                                $ajaxRouteDescriptors[$action][$prefixId] = [
                                    $route->getMethods()[0],
                                    $route->getPath()
                                ];
                            }
                        }
                    }
                }
            }

            $this->cache->set($ajaxRouteDescriptorsKey, $ajaxRouteDescriptors);
        }

        $this->routeDescriptors = $ajaxRouteDescriptors;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        $request = $event->getRequest();

        if ($result instanceof View && !$request->attributes->get('_ajax')) {
            $result->setJsVar('routeParameters', $request->attributes->get('_route_params'));
            $result->setJsVar('routes', $this->routeDescriptors);
        }
    }
}
