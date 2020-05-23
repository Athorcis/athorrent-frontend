<?php

namespace Athorrent\Security\Csrf;

use Athorrent\Notification\Notification;
use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfListener implements EventSubscriberInterface
{
    private $tokenManager;

    public function __construct(CsrfTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethodSafe()) {
            $csrfToken = $this->tokenManager->getToken('main');
        } else {
            $previousCsrfToken = new CsrfToken('main', $request->get('csrfToken'));

            if (!$this->tokenManager->isTokenValid($previousCsrfToken)) {
                throw new AccessDeniedHttpException('invalid csrf token');
            }

            $csrfToken = $this->tokenManager->refreshToken('main');
        }

        $request->attributes->set('_csrfToken', $csrfToken);
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result === null) {
            return;
        }

        $request = $event->getRequest();
        $csrfToken = $request->attributes->get('_csrfToken');

        if ($result instanceof View) {
            $result->setJsVar('csrfToken', $csrfToken->getValue());
        } elseif ($result instanceof Notification) {
            return;
        } elseif (!$request->isMethodSafe()) {
            $result['csrfToken'] = $csrfToken->getValue();
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