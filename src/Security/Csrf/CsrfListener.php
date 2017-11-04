<?php

namespace Athorrent\Security\Csrf;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CsrfListener implements EventSubscriberInterface
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $tokenManager = new TokenManager($session);

        if ($request->getMethod() === 'POST') {
            if (!$tokenManager->isTokenValid($request->get('csrfToken'))) {
                throw new HttpException(403, 'invalid csrf token');
            }

            $csrfToken = $tokenManager->refreshToken();
        } else {
            $csrfToken = $tokenManager->getToken();
        }

        $request->attributes->set('_csrfToken', $csrfToken);
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if ($result === null) {
            return;
        }

        $request = $event->getRequest();
        $csrfToken = $request->attributes->get('_csrfToken');

        if ($result instanceof View) {
            $result->setJsVar('csrfToken', $csrfToken);
        } elseif ($request->getMethod() === 'POST') {
            $result['csrfToken'] = $csrfToken;
        }

        $event->setControllerResult($result);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::VIEW => 'onKernelView',
        ];
    }
}
