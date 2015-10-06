<?php

namespace Athorrent\IPC;

class JsonRequest {
    private $action;

    private $parameters;

    public function __construct($action, $parameters) {
        $this->action = $action;
        $this->parameters = $parameters;
    }

    public function toRawRequest() {
        return json_encode(array('action' => $this->action, 'parameters' => $this->parameters), JSON_FORCE_OBJECT) . "\n";
    }
}

?>
