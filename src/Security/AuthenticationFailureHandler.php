<?php

declare(strict_types=1);

namespace Athorrent\Security;

use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\NotificationListener;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

readonly class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private NotificationListener $notificationListener,
        #[Autowire(env: 'APP_DEBUG')]
        private bool $debug,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($this->debug) {
            dump($exception);
        }

        $notification = new ErrorNotification('error.loginFailure');
        return $this->notificationListener->handleNotification($notification, $request);
    }
}
