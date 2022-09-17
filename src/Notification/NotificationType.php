<?php

namespace Athorrent\Notification;

enum NotificationType: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
}
