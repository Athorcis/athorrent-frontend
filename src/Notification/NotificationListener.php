<?php

namespace Athorrent\Notification;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationListener implements EventSubscriberInterface
{
    private bool $keepCookie = false;

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $this->handleView($result, $request);
        }
        elseif ($result instanceof Notification) {
            $response = $this->handleNotification($result, $request);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->keepCookie) {
            $request = $event->getRequest();

            if ($request->cookies->get('notification')) {
                $event->getResponse()->headers->setCookie(new Cookie('notification'));
            }
        }
    }

    protected function getFlashBag(Request $request): ?FlashBagInterface
    {
        return $request->getSession()->getFlashBag();
    }

    public function handleView(View $view, Request $request): void
    {
        if ($request->cookies->has('notification')) {
            $flashBag = $this->getFlashBag($request);

            if ($flashBag && $flashBag->has('notifications')) {
                $view->set('notifications', $flashBag->get('notifications'));
            }
        }
    }

    public function handleNotification(Notification $notification, Request $request): RedirectResponse
    {
        $flashBag = $this->getFlashBag($request);

        $flashBag?->add('notifications', $notification);
        $this->keepCookie = true;

        $action = $notification->getAction();

        if ($action) {
            $url = $this->urlGenerator->generate($action);
        } else {
            $url = $request->headers->get('Referer');
        }

        return new RedirectResponse($url, 302, [
            'set-cookie' => new Cookie('notification', 1),
        ]);
    }
}
