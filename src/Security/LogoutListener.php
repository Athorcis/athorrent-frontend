<?php

declare(strict_types=1);

namespace Athorrent\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LogoutListener implements EventSubscriberInterface
{
    /**
     * @param string[] $locales
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.locales%')]
        private array $locales,
        #[Autowire('%kernel.default_locale%')]
        private string $defaultLocale,
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        $locale = $event->getRequest()->query->getString('_locale', $this->defaultLocale);

        if (!\in_array($locale, $this->locales, true)) {
            $locale = $this->defaultLocale;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('home', ['_locale' => $locale]),
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 65]];
    }
}
