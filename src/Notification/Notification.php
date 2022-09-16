<?php

namespace Athorrent\Notification;

class Notification
{
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(private string $type, private string $message, private ?string $action = null)
    {
    }

    public function getType(): string
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
