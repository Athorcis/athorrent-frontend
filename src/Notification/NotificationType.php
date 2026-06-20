<?php

declare(strict_types=1);

namespace Athorrent\Notification;

enum NotificationType: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
}
