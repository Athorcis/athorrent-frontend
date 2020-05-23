<?php

namespace Athorrent;

use Athorrent\Security\Nonce\NonceManager;
use Athorrent\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    private $nonceManager;

    public function __construct(NonceManager $nonceManager)
    {
        $this->nonceManager = $nonceManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::RESPONSE => [
                ['addHeaders', 0],
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
                    'debug' => $GLOBALS['debug'],
                    'staticHost' => $_ENV['STATIC_HOST']
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

    public function addHeaders(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($response->headers->has('Content-Disposition')) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isXmlHttpRequest()) {
            $cspScriptSrc = "'strict-dynamic' 'nonce-" . $this->nonceManager->getNonce() . "'";

            // Symfony 4.1 doesn't add the 'unsafe-eval
            // required by the web profiler to work
            if ($GLOBALS['debug']) {
                $cspScriptSrc .= " 'unsafe-eval'";
            }

            $response->headers->set('Content-Security-Policy', 'script-src ' . $cspScriptSrc);
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        }
    }
}
