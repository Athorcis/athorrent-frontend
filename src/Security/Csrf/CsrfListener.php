<?php

namespace Athorrent\Security\Csrf;

use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

readonly class CsrfListener implements EventSubscriberInterface
{
    public function __construct(#[Lazy] private CsrfTokenManagerInterface $tokenManager)
    {
    }

    protected function getRouteOptions(ControllerArgumentsEvent $event): array
    {
        $routeOptions = [];

        /** @var Route[] $routeAttributes */
        $routeAttributes = $event->getAttributes(Route::class);

        foreach ($routeAttributes as $attribute) {
            $routeOptions = array_merge($routeOptions, $attribute->getOptions());
        }

        return $routeOptions;
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethodSafe()) {
            $routeOptions = $this->getRouteOptions($event);
            $delegateCsrf = $routeOptions['delegate_csrf'] ?? false;

            if (!$delegateCsrf) {
                $tokenValue = $request->headers->get('X-Csrf-Token', $request->get('_token'));
                $token = new CsrfToken('xhr', $tokenValue);

                if ($tokenValue === null || !$this->tokenManager->isTokenValid($token)) {
                    throw new AccessDeniedHttpException('error.invalidCsrfToken');
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments',
        ];
    }
}
