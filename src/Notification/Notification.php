<?php

namespace Athorrent\Notification;

class Notification
{
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    private string $type;

    private string $message;

    private ?string $action;

    public function __construct(string $type, string $message, string $action = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->action = $action;
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
