<?php

declare(strict_types=1);

namespace Athorrent;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;
use function Symfony\Component\String\u;

readonly class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        #[Autowire(lazy: true)]
        private Environment $twig,
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
        #[Autowire(env: 'APP_DEBUG')]
        private bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 0],
                ['onEarlyKernelException', 10],
            ]
        ];
    }

    /**
     * @return array{string, ?string, int}
     */
    protected function getMessageKeyAndStatusCode(Throwable $throwable): array
    {
        if ($throwable instanceof HttpException) {
            $statusCode = $throwable->getStatusCode();
        } else {
            $statusCode = 500;
        }

        $rawMessage = $throwable->getMessage();

        if (str_starts_with($rawMessage, 'error.')) {
            $messageKey = $rawMessage;
        }
        elseif ($throwable instanceof NotFoundHttpException) {
            $messageKey = 'error.pageNotFound';
        } else {
            $messageKey = 'error.unknownError';
        }

        $message = $this->translator->trans($messageKey);

        if ($message !== $messageKey) {
            $errorCode = u(
                preg_replace('/^error\./', '', $messageKey)
            )->snake()->upper()->toString();
        }
        else {
            $errorCode = null;
        }

        return [$message, $errorCode, $statusCode];
    }

    protected function renderError(Request $request, string $message, ?string $errorCode, int $statusCode): Response
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse([
                'status' => 'error',
                'code' => $errorCode,
                'error' => $message,
            ], $statusCode);
        } else {
            try {
                $html = $this->twig->render('pages/error.html.twig', ['error' => $message, 'code' => $statusCode, 'js_vars' => []]);
                $response = new Response($html);
            }
            catch (Throwable) {
                $response = new Response($message, $statusCode, ['Content-Type' => 'text/plain']);
            }
        }

        return $response;
    }

    public function onEarlyKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof AccessDeniedException) {
            if ($this->tokenStorage->getToken() === null && $event->getRequest()->isXmlHttpRequest()) {
                $event->allowCustomResponseCode();
                $event->setResponse(new JsonResponse([
                    'status' => 'error',
                    'code' => 'LOGIN_REQUIRED',
                ]));
            }
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->debug && !$event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $throwable = $event->getThrowable();
        [$message, $errorCode, $statusCode] = $this->getMessageKeyAndStatusCode($throwable);
        $response = $this->renderError($event->getRequest(), $message, $errorCode, $statusCode);

        $response->setStatusCode($statusCode);
        $event->setResponse($response);

        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
    }
}
