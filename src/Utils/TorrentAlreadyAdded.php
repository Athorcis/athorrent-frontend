<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Exception;
use Throwable;

class TorrentAlreadyAdded extends Exception
{
    public function __construct(readonly string $torrent, ?Throwable $previous = null)
    {
        parent::__construct("torrent already added : " . $torrent, 0, $previous);
    }
}
