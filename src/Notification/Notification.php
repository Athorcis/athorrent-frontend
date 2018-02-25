<?php

namespace Athorrent\Notification;

class Notification
{
    const SUCCESS = 'success';
    const WARNING = 'warning';
    const ERROR = 'error';

    private $type;

    private $message;

    private $action;

    public function __construct($type, $message, $action = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->action = $action;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getAction()
    {
        return $this->action;
    }
}
