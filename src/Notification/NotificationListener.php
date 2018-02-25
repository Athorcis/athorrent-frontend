<?php

namespace Athorrent\Notification;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationListener implements EventSubscriberInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::VIEW => 'onKernelView'];
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $response = $this->handleView($result, $request);
        } elseif ($result instanceof Notification) {
            $response = $this->handleNotification($result, $request);
        }

        if (isset($response)) {
            $event->setResponse($response);
        }
    }

    public function handleView(View $view, Request $request)
    {
        $flashBag = $request->getSession()->getFlashBag();

        if ($flashBag->has('notifications')) {
            $view->set('notifications', $flashBag->get('notifications'));
        }
    }

    public function handleNotification(Notification $notification, Request $request)
    {
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->add('notifications', $notification);

        $action = $notification->getAction();

        if ($action) {
            $url = $this->urlGenerator->generate($action);
        } else {
            $url = $request->headers->get('Referer');
        }

        return new RedirectResponse($url);
    }
}
