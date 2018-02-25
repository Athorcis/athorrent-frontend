<?php

namespace Athorrent\Notification;

class ErrorNotification extends Notification
{
    public function __construct($message, $action = null)
    {
        parent::__construct(Notification::ERROR, $message, $action);
    }
}
