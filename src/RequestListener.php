<?php

namespace Athorrent;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::RESPONSE => [
                ['saveSession', -512]
            ]
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $request = $event->getRequest();

            if (!$request->isXmlHttpRequest()) {
                $result->addTemplate('modal');

                $vars = [
                    'debug' => (bool)$_SERVER['APP_DEBUG'],
                    'assetsOrigin' => $_ENV['ASSETS_ORIGIN']
                ];

                $result->setJsVars($vars);
            }
        }
    }

    public function saveSession(ResponseEvent $event): void
    {
        $session = $event->getRequest()->getSession();

        if ($session && $session->isStarted()) {
            $session->save();
        }
    }
}
