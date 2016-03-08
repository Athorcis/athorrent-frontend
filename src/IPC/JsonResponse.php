<?php

namespace Athorrent\IPC;

class JsonResponse {
    private $data;

    private $success;

    public function __construct($data, $success = true) {
        $this->data = $data;
        $this->success = $success;
    }

    public function isSuccess() {
        return $this->success;
    }

    public function getData() {
        return $this->data;
    }

    public static function parse($rawResponse) {
        $array = json_decode($rawResponse, true);

        if (!isset($array['status'])) {
            return null;
        }

        $status = $array['status'];

        if ($status != 'success' && $status != 'error') {
            return null;
        }

        $success = $status === 'success';

        if (!isset($array['data'])) {
            return null;
        }

        return new JsonResponse($array['data'], $success);
    }
}

?>
