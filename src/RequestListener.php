<?php

declare(strict_types=1);

namespace Athorrent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['saveSession', -512],
                ['addVaryHeader'],
                ['disableOutputBuffering'],
            ]
        ];
    }

    /**
     * Release the session lock before long-running file responses.
     *
     * Do not save on every response: closing the session before Symfony's
     * SessionListener (-1000) means the session cookie is never added to the
     * Response. With mock_file storage (test env) there is no native setcookie(),
     * so the cookie is lost and values like _security.*.target_path disappear
     * on the next request (e.g. after redirect to login).
     */
    public function saveSession(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof BinaryFileResponse && !$response instanceof StreamedResponse) {
            return;
        }

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
        if ($event->getResponse() instanceof BinaryFileResponse && ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
