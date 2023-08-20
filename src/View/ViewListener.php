<?php

namespace Athorrent\View;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ViewListener implements EventSubscriberInterface
{
    public function __construct(private TranslatorInterface $translator, private Renderer $renderer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => [
            ['onEarlyKernelView', 255],
            ['onLateKernelView', -255]
        ]];
    }

    public function onEarlyKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $xhr = $request->isXmlHttpRequest();

        if (!$xhr) {
            return;
        }

        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $data = $result->render($request, $this->translator, $this->renderer);
        } elseif ($result === null || is_array($result)) {
            $data = $result;
        } else {
            return;
        }

        $event->setControllerResult([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function onLateKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $response = new Response($result->render($event->getRequest(), $this->translator, $this->renderer));
        } elseif (is_array($result)) {
            $response = new JsonResponse($result);
        }

        if (isset($response)) {
            $event->setResponse($response);
        }
    }
}
