<?php

namespace Athorrent\Notification;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationListener implements EventSubscriberInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => 'onKernelView'];
    }

    public function onKernelView(ViewEvent $event): void
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

    protected function getFlashBag(Request $request)
    {
        $session = $request->getSession();

        if ($session instanceof Session) {
            return $session->getFlashBag();
        }

        return null;
    }

    public function handleView(View $view, Request $request)
    {
        $flashBag = $this->getFlashBag($request);

        if ($flashBag && $flashBag->has('notifications')) {
            $view->set('notifications', $flashBag->get('notifications'));
        }
    }

    public function handleNotification(Notification $notification, Request $request): RedirectResponse
    {
        $flashBag = $this->getFlashBag($request);

        if ($flashBag) {
            $flashBag->add('notifications', $notification);
        }

        $action = $notification->getAction();

        if ($action) {
            $url = $this->urlGenerator->generate($action);
        } else {
            $url = $request->headers->get('Referer');
        }

        return new RedirectResponse($url);
    }
}
