<?php

declare(strict_types=1);

namespace Athorrent\Notification;

class SuccessNotification extends Notification
{
    public function __construct($message, $action = null)
    {
        parent::__construct(NotificationType::SUCCESS, $message, $action);
    }
}
