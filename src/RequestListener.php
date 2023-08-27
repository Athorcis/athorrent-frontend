<?php

namespace Athorrent;

use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                ['saveSession', -512],
                ['addVaryHeader'],
                ['disableOutputBuffering'],
            ]
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $request = $event->getRequest();

            if (!$request->isXmlHttpRequest()) {
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

        if ($session->isStarted()) {
            $session->save();
        }
    }

    public function addVaryHeader(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('Vary', 'X-Requested-With');
    }

    /**
     * Disable output buffering when returning a BinaryFileResponse
     * A memory error happens on certain versions of PHP when writing on php://output
     * with stream_copy_to_stream if output buffering is enabled (PHP 8.1.10 on Windows)
     */
    public function disableOutputBuffering(ResponseEvent $event): void
    {
        if ($event->getResponse() instanceof BinaryFileResponse) {
            ob_end_flush();
        }
    }
}
