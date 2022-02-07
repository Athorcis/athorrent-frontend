<?php

namespace Athorrent\Utils\Search;

class TorrentInfo
{
    public string $name;

    public string $href;

    public int $age;

    public string $magnet;

    public int $size;

    public int $seeders;

    public int $leechers;

    public function __construct(
        string $name,
        string $href,
        int $age,
        string $magnet,
        int $size,
        int $seeders,
        int $leechers
    )
    {
        $this->name = $name;
        $this->href = $href;
        $this->age = $age;
        $this->magnet = $magnet;
        $this->size = $size;
        $this->seeders = $seeders;
        $this->leechers = $leechers;
    }
}
