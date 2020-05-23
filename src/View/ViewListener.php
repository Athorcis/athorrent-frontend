<?php

namespace Athorrent\View;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class ViewListener implements EventSubscriberInterface
{
    private $translator;

    private $renderer;

    public function __construct(TranslatorInterface $translator, Renderer $renderer)
    {
        $this->translator = $translator;
        $this->renderer = $renderer;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::VIEW => [
            ['onEarlyKernelView', 255],
            ['onLateKernelView', -255]
        ]];
    }

    public function onEarlyKernelView(ViewEvent $event): void
    {
        $xhr = $event->getRequest()->isXmlHttpRequest();

        if (!$xhr) {
            return;
        }

        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $data = $result->render($this->translator, $this->renderer);
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
            $response = new Response($result->render($this->translator, $this->renderer));
        } elseif (is_array($result)) {
            $response = new JsonResponse($result);
        }

        if (isset($response)) {
            $event->setResponse($response);
        }
    }
}
