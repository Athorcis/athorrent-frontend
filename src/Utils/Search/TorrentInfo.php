<?php

namespace Athorrent\Utils\Search;

class TorrentInfo
{
    /** @var string */
    public $name;

    /** @var string */
    public $href;

    /** @var int */
    public $age;

    /** @var string */
    public $magnet;

    /** @var int */
    public $size;

    /** @var int */
    public $seeders;

    /** @var int */
    public $leechers;

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
