<?php

namespace Athorrent\Routing;

use Athorrent\View\View;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RoutingListener implements EventSubscriberInterface
{
    private array $routeDescriptors;

    public function __construct(private CacheInterface $cache, private RequestContext $requestContext, private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->initAjaxRouteDescriptors($request);

        $attributes = $request->attributes;

        $this->requestContext->setParameter('_prefixId', $attributes->get('_prefixId'));

        foreach ($attributes->get('_route_params', []) as $key => $value) {
            if ($key[0] !== '_') {
                $this->requestContext->setParameter($key, $value);
            }
        }
    }

    protected function initAjaxRouteDescriptors(Request $request): void
    {
        $locale = $request->getLocale();
        $ajaxRouteDescriptorsKey = 'ajax_route_descriptors_' . $locale;

        if ($this->cache->has($ajaxRouteDescriptorsKey)) {
            $ajaxRouteDescriptors = $this->cache->get($ajaxRouteDescriptorsKey);
        } else {
            $ajaxRouteDescriptors = [];

            foreach ($this->router->getRouteCollection() as $route) {
                if ($route->hasDefault('_action')) {
                    $action = $route->getDefault('_action');
                    $prefixId = $route->getDefault('_prefixId');

                    if ($route->getOption('expose')) {
                        $defaultLocale = $route->getDefault('_locale');

                        if ($locale === 'fr') {
                            if ($defaultLocale) {
                                $ajaxRouteDescriptors[$action][$prefixId] = [
                                    'method' => $route->getMethods()[0],
                                    'pattern' => $route->getPath()
                                ];
                            }
                        } elseif (!$defaultLocale) {
                            $ajaxRouteDescriptors[$action][$prefixId] = [
                                'method' => $route->getMethods()[0],
                                'pattern' => $route->getPath()
                            ];
                        }
                    }
                }
            }

            $this->cache->set($ajaxRouteDescriptorsKey, $ajaxRouteDescriptors);
        }

        $this->routeDescriptors = $ajaxRouteDescriptors;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        $request = $event->getRequest();

        if ($result instanceof View && !$request->isXmlHttpRequest()) {
            $result->setJsVar('routeParameters', $request->attributes->get('_route_params'));
            $result->setJsVar('routes', $this->routeDescriptors);
        }
    }
}
