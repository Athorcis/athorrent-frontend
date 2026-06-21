<?php

namespace Athorrent;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SharingNotFoundException extends NotFoundHttpException
{
    public function __construct(?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct('error.sharingNotFound', $previous, $code, $headers);
    }
}
