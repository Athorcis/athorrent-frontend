<?php

namespace Athorrent\Security;

use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\NotificationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

readonly class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private NotificationListener $notificationListener)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($_SERVER['APP_DEBUG']) {
            dump($exception);
        }

        $notification = new ErrorNotification('error.loginFailure');
        return $this->notificationListener->handleNotification($notification, $request);
    }
}
