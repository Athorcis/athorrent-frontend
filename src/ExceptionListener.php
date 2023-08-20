<?php

namespace Athorrent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

readonly class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(private TranslatorInterface $translator, private Environment $twig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    /**
     * @return array{string, int}
     */
    protected function getMessageAndStatusCode(Throwable $throwable): array
    {
        if ($throwable instanceof HttpException) {
            $statusCode = $throwable->getStatusCode();

            if ($throwable instanceof NotFoundHttpException) {
                $message = 'error.pageNotFound';
            }
        } else {
            $statusCode = 500;
        }

        if ($statusCode === 500) {
            $message = 'error.errorUnknown';
        }

        if (isset($message)) {
            $message = $this->translator->trans($message);
        } else {
            $message = $throwable->getMessage();
        }

        return [$message, $statusCode];
    }

    protected function renderError(Request $request, string $message, int $statusCode): Response
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse([
                'status' => 'error',
                'error' => $message
            ], $statusCode);
        } else {
            $html = $this->twig->render('pages/error.html.twig', ['error' => $message, 'code' => $statusCode]);
            $response = new Response($html);
        }

        return $response;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($_SERVER['APP_DEBUG']) {
            return;
        }

        [$message, $statusCode] = $this->getMessageAndStatusCode($event->getThrowable());
        $response = $this->renderError($event->getRequest(), $message, $statusCode);

        $response->setStatusCode($statusCode);
        $event->setResponse($response);
    }
}
