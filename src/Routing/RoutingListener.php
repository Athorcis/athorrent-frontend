<?php

namespace Athorrent\Routing;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

class RoutingListener implements EventSubscriberInterface
{
    private $requestContext;

    private $routeDescriptorsProvider;

    public function __construct(RequestContext $requestContext, $routeDescriptorsProvider)
    {
        $this->requestContext = $requestContext;
        $this->routeDescriptorsProvider = $routeDescriptorsProvider;
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
        $attributes = $event->getRequest()->attributes;

        $this->requestContext->setParameter('_prefixId', $attributes->get('_prefixId'));

        foreach ($attributes->get('_route_params') as $key => $value) {
            if ($key[0] !== '_') {
                $this->requestContext->setParameter($key, $value);
            }
        }
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        $request = $event->getRequest();

        if ($result instanceof View && !$request->attributes->get('_ajax')) {
            $result->setJsVar('routes', ($this->routeDescriptorsProvider)());
        }
    }
}
