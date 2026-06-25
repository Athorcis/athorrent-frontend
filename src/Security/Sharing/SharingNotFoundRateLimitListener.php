<?php

declare(strict_types=1);

namespace Athorrent\Security\Sharing;

use Athorrent\SharingNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Throwable;

readonly class SharingNotFoundRateLimitListener implements EventSubscriberInterface
{
    public function __construct(
        #[Target('sharing_not_found')]
        private RateLimiterFactoryInterface $sharingNotFoundLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 5],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof SharingNotFoundException) {
            $this->processSharingNotFoundException($event);
        }
    }

    protected function processSharingNotFoundException($event)
    {
        $limiter = $this->sharingNotFoundLimiter->create(
            $event->getRequest()->getClientIp() ?? 'unknown',
        );
        $rateLimit = $limiter->consume(1);

        if (!$rateLimit->isAccepted()) {
            $event->setThrowable(new TooManyRequestsHttpException(
                $rateLimit->getRetryAfter()->getTimestamp(),
            ));
        }
    }
}
