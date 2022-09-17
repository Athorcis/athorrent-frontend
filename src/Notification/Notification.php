<?php

namespace Athorrent\Notification;

class Notification
{
    public function __construct(private NotificationType $type, private string $message, private ?string $action = null)
    {
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }
}
