<?php

namespace Athorrent\Notification;

class Notification
{
    public function __construct(private readonly NotificationType $type, private readonly string $message, private readonly ?string $action = null)
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
