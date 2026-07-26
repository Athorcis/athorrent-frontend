<?php

declare(strict_types=1);

namespace Athorrent\Security;

use Athorrent\Utils\ContentSecurityPolicy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Merges ADDITIONAL_CSP_HEADER into the response CSP, and allows
 * style-src 'unsafe-inline' on media play pages for @videojs/html.
 */
readonly class ContentSecurityPolicyListener implements EventSubscriberInterface
{
    private const array PLAY_SUBROUTES = ['playAudio', 'playVideo'];

    public function __construct(
        #[Autowire('%env(string:default::ADDITIONAL_CSP_HEADER)%')]
        private string $additionalCspHeader,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // After Nelmio sets Content-Security-Policy
            KernelEvents::RESPONSE => ['onKernelResponse', -16],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $overlay = ContentSecurityPolicy::parse($this->additionalCspHeader);

        $subroute = $event->getRequest()->attributes->get('_subroute');
        if (\in_array($subroute, self::PLAY_SUBROUTES, true)) {
            $overlay = $overlay->withSources('style-src', "'unsafe-inline'");
        }

        $overlayValue = $overlay->toString();
        if ($overlayValue === '') {
            return;
        }

        $headers = $event->getResponse()->headers;
        $value = $headers->get('Content-Security-Policy');

        if ($value === null || $value === '') {
            return;
        }

        $headers->set(
            'Content-Security-Policy',
            ContentSecurityPolicy::mergeHeaders($value, $overlayValue),
        );
    }
}
