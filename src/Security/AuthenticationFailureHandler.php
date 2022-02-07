<?php

namespace Athorrent\Security;

use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\NotificationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private NotificationListener $notificationListener;

    public function __construct(NotificationListener $notificationListener)
    {
        $this->notificationListener = $notificationListener;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($_SERVER['APP_DEBUG']) {
            dump($exception);
            exit(0);
        }
        $notification = new ErrorNotification('error.loginFailure');
        return $this->notificationListener->handleNotification($notification, $request);
    }
}
