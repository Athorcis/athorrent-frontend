<?php

namespace Athorrent;

use Athorrent\Application\WebApplication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Translator;

class ExceptionListener implements EventSubscriberInterface
{
    private $translator;

    private $twig;

    private $app;

    public function __construct(Translator $translator, \Twig_Environment $twig, WebApplication $app)
    {
        $this->translator = $translator;
        $this->twig = $twig;
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (DEBUG) {
            return;
        }

        $exception = $event->getException();

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        if ($exception instanceof NotFoundHttpException) {
            $error = 'error.pageNotFound';
        }

        if ($statusCode === 500) {
            $error = 'error.errorUnknown';
        }

        if (isset($error)) {
            $error = $this->translator->trans($error);
        } else {
            $error = $exception->getMessage();
        }

        $request = $event->getRequest();

        if ($request->attributes->get('_ajax')) {
            $response = new JsonResponse([
                'status' => 'error',
                'error' => $error
            ], $statusCode);
        } else {
            $html = $this->twig->render('pages/error.html.twig', ['error' => $error, 'code' => $statusCode]);
            $response = new Response($html);
        }

        $response->setStatusCode($statusCode);
        $event->setResponse($response);
    }
}
