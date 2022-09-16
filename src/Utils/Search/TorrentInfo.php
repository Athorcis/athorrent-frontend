<?php

namespace Athorrent\Utils\Search;

class TorrentInfo
{
    public function __construct(public string $name, public string $href, public int $age, public string $magnet, public int $size, public int $seeders, public int $leechers)
    {
    }
}
