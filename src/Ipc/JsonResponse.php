<?php

namespace Athorrent\Ipc;

class JsonResponse
{
    private mixed $data;

    private bool $success;

    public function __construct(mixed $data, bool $success = true)
    {
        $this->data = $data;
        $this->success = $success;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public static function parse($rawResponse): ?JsonResponse
    {
        $array = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($array['status'])) {
            return null;
        }

        $status = $array['status'];

        if ($status !== 'success' && $status !== 'error') {
            return null;
        }

        $success = $status === 'success';

        if (!isset($array['data'])) {
            return null;
        }

        return new self($array['data'], $success);
    }
}
