<?php

namespace Athorrent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ExceptionListener implements EventSubscriberInterface
{
    private $translator;

    private $twig;

    public function __construct(TranslatorInterface $translator, Environment $twig)
    {
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($_SERVER['APP_DEBUG']) {
            return;
        }

        $exception = $event->getThrowable();

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

        if ($request->isXmlHttpRequest()) {
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
